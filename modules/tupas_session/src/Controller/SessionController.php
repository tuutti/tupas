<?php

namespace Drupal\tupas_session\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas\Form\TupasFormBase;
use Drupal\tupas_session\Event\CustomerIdAlterEvent;
use Drupal\tupas_session\Event\RedirectAlterEvent;
use Drupal\tupas_session\Event\SessionEvents;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\tupas_session\TupasTransactionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Tupas\Form\TupasForm;
use Tupas\Tupas;

/**
 * Class SessionController.
 *
 * @package Drupal\tupas_session\Controller
 */
class SessionController extends ControllerBase {

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Tupas session manager service.
   *
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The transaction manager.
   *
   * @var \Drupal\tupas_session\TupasTransactionManagerInterface
   */
  protected $transactionManager;

  /**
   * The storage.
   *
   * @var \Drupal\tupas\TupasBankStorage
   */
  protected $storage;

  /**
   * SessionController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   Tupas session manager service.
   * @param \Drupal\tupas_session\TupasTransactionManagerInterface $transaction_manager
   *   The transaction manager.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, TupasSessionManagerInterface $session_manager, TupasTransactionManagerInterface $transaction_manager) {
    $this->eventDispatcher = $event_dispatcher;
    $this->sessionManager = $session_manager;
    $this->transactionManager = $transaction_manager;
    $this->storage = $this->entityTypeManager()->getStorage('tupas_bank');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('tupas_session.session_manager'),
      $container->get('tupas_session.transaction_manager')
    );
  }

  /**
   * Helper to generate (absolute) internal URLs.
   *
   * @param string $key
   *   Route.
   *
   * @return string
   *   Absolute url to given route.
   */
  public function fromRoute($key) {
    $url = new Url($key, [], ['absolute' => TRUE]);

    return $url->toString();
  }

  /**
   * Callback for /user/tupas/login path.
   *
   * @return array
   *   Render array.
   */
  public function front() {
    if ($this->sessionManager->getSession()) {
      $this->messenger()->addWarning($this->t('You already have an active TUPAS session.'));
    }
    $banks = $this->storage->getEnabled();

    $content['tupas_bank_items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tupas-bank-items']],
    ];
    // Regenerate transaction id every page refresh.
    $transaction_id = $this->transactionManager->regenerate();

    /** @var \Drupal\tupas\Entity\TupasBank $bank */
    foreach ($banks as $bank) {
      if ($this->moduleHandler()->moduleExists('tupas_registration')) {
        // Show only banks that allows registration (correct id type) when using
        // tupas_registration.
        if (!$bank->validIdType()) {
          continue;
        }
      }
      $form = new TupasForm($bank);
      // Populate required settings.
      $form->setCancelUrl($this->fromRoute('tupas_session.return'))
        ->setRejectedUrl($this->fromRoute('tupas_session.canceled'))
        ->setReturnUrl($this->fromRoute('tupas_session.return'))
        ->setLanguage($this->languageManager()->getCurrentLanguage()->getId())
        ->setTransactionId($transaction_id);

      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm(TupasFormBase::class, $form, $bank);
    }
    return $content;
  }

  /**
   * Callback for /user/tupas/authenticated path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function returnTo(Request $request) {
    if (!$stamp = $request->query->get('B02K_TIMESTMP')) {
      $this->messenger()->addError($this->t('Missing required bank id argument.'));

      return $this->redirect('<front>');
    }
    $bank_number = (int) substr($stamp, 0, 3);
    $bank = $this->storage
      ->loadByBankNumber($bank_number);

    if (!$bank instanceof TupasBank) {
      $this->messenger()->addError($this->t('Validation failed.'));

      return $this->redirect('<front>');
    }
    $tupas = new Tupas($bank, $request->query->all());
    $transaction_id = (string) $this->transactionManager->get();

    try {
      // Session not found / expired.
      if (!$tupas->isValidTransaction($transaction_id)) {
        throw new \InvalidArgumentException('Invalid transaction.');
      }
    }
    catch (\InvalidArgumentException $e) {
      $this->messenger()->addError($this->t('Transaction not found or expired.'));

      return $this->redirect('tupas_session.front');
    }

    try {
      $tupas->validate();
      // Hash customer id.
      $hashed_id = $bank->hashResponseId($request->query->get('B02K_CUSTID'));

      // Allow customer id to be altered.
      /** @var \Drupal\tupas_session\Event\CustomerIdAlterEvent $dispatched_data */
      $dispatched_data = $this->eventDispatcher
        ->dispatch(SessionEvents::CUSTOMER_ID_ALTER, new CustomerIdAlterEvent($hashed_id, [
          'raw' => $request->query->all(),
        ]));
      // Name will be sent Latin1 encoded and urlencoded.
      $name = Unicode::convertToUtf8(urldecode($request->query->get('B02K_CUSTNAME')), 'ISO-8859-1');
      // Start tupas session.
      $this->sessionManager->start($transaction_id, $dispatched_data->getCustomerId(), [
        'bank' => $bank->id(),
        'name' => $name,
      ]);
      // Allow redirect path to be customized.
      $redirect_data = new RedirectAlterEvent('<front>', $request->query->all(), $this->t('TUPAS authentication succesful.'));
      /** @var \Drupal\tupas_session\Event\RedirectAlterEvent $redirect */
      $redirect = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_ALTER, $redirect_data);

      // Show message only if message is set.
      if ($message = $redirect->getMessage()) {
        $this->messenger()->addMessage($message);
      }
      // Delete used transaction after succesful tupas authentication.
      $this->transactionManager->delete();

      return $this->redirect($redirect->getPath(), $redirect->getArguments());
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('TUPAS authentication failed.'));

      return $this->redirect('<front>');
    }
  }

  /**
   * Callback for /user/tupas/cancel path.
   */
  public function cancel() {
    // Attempt to delete transaction id.
    $this->transactionManager->delete();

    $this->messenger()->addWarning($this->t('TUPAS authentication was canceled by user.'));

    return $this->redirect('<front>');
  }

  /**
   * Callback for /user/tupas/rejected path.
   */
  public function rejected() {
    // Attempt to delete transaction id.
    $this->transactionManager->delete();

    $this->messenger()->addWarning($this->t('TUPAS authentication was rejected.'));

    return $this->redirect('<front>');
  }

  /**
   * Callback for /user/tupas/logout path.
   */
  public function logout() {
    // Log the user out if user is authenticated.
    if ($this->currentUser()->isAuthenticated()) {
      user_logout();
    }
    $this->sessionManager->destroy();

    return $this->redirect('<front>');
  }

}
