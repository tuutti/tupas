<?php

namespace Drupal\tupas_session\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas_session\Event\CustomerIdAlterEvent;
use Drupal\tupas_session\Event\RedirectAlterEvent;
use Drupal\tupas_session\Event\SessionEvents;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\tupas_session\TupasTransactionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * Callback for /user/tupas/login path.
   *
   * @return array
   *   Render array.
   */
  public function front() {
    if ($this->sessionManager->getSession()) {
      drupal_set_message($this->t('You already have an active TUPAS session.'), 'warning');
    }
    $banks = $this->entityTypeManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

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
      // Populate required settings.
      $bank->setSettings([
        // Attempt to use current language. Fallback to english.
        'language' => $this->languageManager()->getCurrentLanguage()->getId(),
        'return_url' => 'tupas_session.return',
        'cancel_url' => 'tupas_session.canceled',
        'rejected_url' => 'tupas_session.return',
        'transaction_id' => $transaction_id,
      ]);
      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasFormBase', $bank);
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
    if (!$request->query->get('bank_id')) {
      drupal_set_message($this->t('Missing required bank id argument.'), 'error');

      return $this->redirect('<front>');
    }
    $bank = $this->entityTypeManager()
      ->getStorage('tupas_bank')
      ->load($request->query->get('bank_id'));

    if (!$bank instanceof TupasBank) {
      drupal_set_message($this->t('Validation failed.'), 'error');

      return $this->redirect('<front>');
    }
    $transaction_id = $bank->parseTransactionId($request->query->get('B02K_STAMP'));

    // Session not found / expired.
    if ($transaction_id != $this->transactionManager->get()) {
      drupal_set_message($this->t('Transaction not found or expired.'), 'error');

      return $this->redirect('tupas_session.front');
    }

    try {
      $bank->validate($request->query->all());
      // Hash customer id.
      $hashed_id = $bank->hashResponseId($request->query->get('B02K_CUSTID'));

      // Allow customer id to be altered.
      /** @var CustomerIdAlterEvent $dispatched_data */
      $dispatched_data = $this->eventDispatcher
        ->dispatch(SessionEvents::CUSTOMER_ID_ALTER, new CustomerIdAlterEvent($hashed_id, [
          'raw' => $request->query->all(),
        ]));
      // Start tupas session.
      $this->sessionManager->start($transaction_id, $dispatched_data->getCustomerId(), [
        'bank' => $bank->id(),
        'name' => $request->query->get('B02K_CUSTNAME'),
      ]);
      // Allow redirect path to be customized.
      $redirect_data = new RedirectAlterEvent('<front>', $request->query->all(), $this->t('TUPAS authentication succesful.'));
      /** @var RedirectAlterEvent $redirect */
      $redirect = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_ALTER, $redirect_data);

      // Show message only if message is set.
      if ($message = $redirect->getMessage()) {
        drupal_set_message($message);
      }
      // Delete used transaction after succesful tupas authentication.
      $this->transactionManager->delete();

      return $this->redirect($redirect->getPath(), $redirect->getArguments());
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('TUPAS authentication failed.'), 'error');

      return $this->redirect('<front>');
    }
  }

  /**
   * Callback for /user/tupas/cancel path.
   */
  public function cancel() {
    // Attempt to delete transaction id.
    $this->transactionManager->delete();

    drupal_set_message($this->t('TUPAS authentication was canceled by user.'), 'warning');

    return $this->redirect('<front>');
  }

  /**
   * Callback for /user/tupas/rejected path.
   */
  public function rejected() {
    // Attempt to delete transaction id.
    $this->transactionManager->delete();

    drupal_set_message($this->t('TUPAS authentication was rejected.'), 'warning');

    return $this->redirect('<front>');
  }

}
