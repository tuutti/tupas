<?php

namespace Drupal\tupas_session\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas\TupasService;
use Drupal\tupas_session\Event\MessageAlterEvent;
use Drupal\tupas_session\Event\RedirectAlterEvent;
use Drupal\tupas_session\Event\SessionEvents;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\tupas_session\TupasTransactionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
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
    $banks = $this->entityManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

    $content['tupas_bank_items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tupas-bank-items']],
    ];
    // Regenerate transaction id every page refresh.
    $transaction_id = $this->transactionManager->regenerate();

    foreach ($banks as $bank) {
      if ($this->moduleHandler()->moduleExists('tupas_registration')) {
        // Show only banks that allows registration (correct id type) when using tupas_registration.
        if (!TupasService::validateIdType($bank->getIdType())) {
          continue;
        }
      }
      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasFormBase', new TupasService($bank, [
          // Attempt to use current language. Fallback to english.
          'language' => $this->languageManager()->getCurrentLanguage()->getId(),
          'return_url' => 'tupas_session.return',
          'cancel_url' => 'tupas_session.canceled',
          'rejected_url' => 'tupas_session.return',
          'transaction_id' => $transaction_id,
        ]));
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
    $bank = $this->entityManager()
      ->getStorage('tupas_bank')
      ->load($request->query->get('bank_id'));

    if (!$bank instanceof TupasBank) {
      drupal_set_message($this->t('Validation failed.'), 'error');

      return $this->redirect('<front>');
    }
    $tupas = new TupasService($bank);
    $transaction_id = $tupas->parseTransactionId($request->query->get('B02K_STAMP'));

    // Session not found / expired.
    if ($transaction_id != $this->transactionManager->get()) {
      drupal_set_message($this->t('Transaction not found or expired.'), 'error');

      return $this->redirect('tupas_session.front');
    }

    try {
      $tupas->validate($request->query->all());
      // Hash customer id.
      $customer_id = TupasService::hashResponseId($request->get('B02K_CUSTID'), $bank->getIdType());

      // Start tupas session.
      $this->sessionManager->start($transaction_id, $customer_id, [
        'bank' => $bank->id(),
        'name' => $request->query->get('B02K_CUSTNAME'),
      ]);
      $message = $this->eventDispatcher->dispatch(SessionEvents::MESSAGE_ALTER, new GenericEvent($this->t('TUPAS authentication succesful.')));
      // Allow message to be altered.
      if ($message->getSubject()) {
        drupal_set_message($message->getSubject());
      }
      // Allow  redirect path to be customized.
      $uri = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_ALTER, new RedirectAlterEvent('<front>'));
      // Delete used transaction after succesful tupas authentication.
      $this->transactionManager->delete();

      return $this->redirect($uri->getPath());
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
