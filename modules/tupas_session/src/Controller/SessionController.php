<?php

namespace Drupal\tupas_session\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tupas\TupasService;
use Drupal\tupas_session\Event\MessageAlterEvent;
use Drupal\tupas_session\Event\RedirectAlterEvent;
use Drupal\tupas_session\Event\SessionEvents;
use Drupal\tupas_session\TupasSessionManagerInterface;
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
   * SessionController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   Tupas session manager service.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, TupasSessionManagerInterface $session_manager) {
    $this->eventDispatcher = $event_dispatcher;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('tupas_session.session_manager')
    );
  }

  /**
   * Callback for /user/tupas/login path.
   *
   * @return array
   */
  public function front() {
    $banks = $this->entityManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

    $content['tupas_bank_items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tupas-bank-items']],
    ];
    $config = $this->config('tupas_session.settings');

    foreach ($banks as $bank) {
      $tupas = new TupasService($bank, [
        'language' => 'FI',
        'return_url' => $config->get('authenticated_goto'),
        'cancel_url' => $config->get('canceled_goto'),
        'rejected_url' => $config->get('rejected_goto'),
        'transaction_id' => rand(100000, 999999),
      ]);
      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasFormBase', $tupas);
    }
    return $content;
  }

  /**
   * Callback for /user/tupas/authenticated path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function returnTo(Request $request) {
    $bank = $this->entityManager()
      ->getStorage('tupas_bank')
      ->load($request->query->get('bank_id'));

    if (!$bank instanceof TupasBank) {
      throw new HttpException(502, 'Bank not found');
    }
    $hash_match = $this->tupas->isValid($request);

    if (!$hash_match) {
      throw new HttpException(502, 'Hash validation failed');
    }
    // Allow message to be customized.
    $message = $this->eventDispatcher->dispatch(SessionEvents::MESSAGE_ALTER, new MessageAlterEvent($this->t('TUPAS authentication succesful.')));
    // Allow message to be disabled.
    if ($message) {
      drupal_set_message($message->getMessage());
    }

    // Start tupas session.
    $this->sessionManager->start($this->currentUser()->id(), $request->query->get('transaction_id'));

    // Allow  redirect path to be customized.
    $uri = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_ALTER, new RedirectAlterEvent('<front>'));

    return $this->redirect($uri->getPath());
  }

  /**
   * Callback for /user/tupas/cancel path.
   */
  public function cancel() {
    // Allow message to be customized.
    $message = $this->eventDispatcher->dispatch(SessionEvents::MESSAGE_CANCEL_ALTER, new MessageAlterEvent($this->t('TUPAS authentication was canceled by used.')));

    // Allow message to be disabled.
    if ($message) {
      drupal_set_message($message->getMessage());
    }

    // Allow  redirect path to be customized.
    $uri = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_CANCEL_ALTER, new RedirectAlterEvent('<front>'));

    return $this->redirect($uri->getPath());
  }

  /**
   * Callback for /user/tupas/rejected path.
   */
  public function rejected() {
    // Allow message to be customized.
    $message = $this->eventDispatcher->dispatch(SessionEvents::MESSAGE_REJECTED_ALTER, new MessageAlterEvent($this->t('TUPAS authentication was rejected.')));

    // Allow message to be disabled.
    if ($message) {
      drupal_set_message($message->getMessage());
    }

    // Allow  redirect path to be customized.
    $uri = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_REJECTED_ALTER, new RedirectAlterEvent('<front>'));

    return $this->redirect($uri->getPath());
  }

}
