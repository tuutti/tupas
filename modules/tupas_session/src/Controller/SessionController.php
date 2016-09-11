<?php

namespace Drupal\tupas_session\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tupas\Entity\TupasBank;
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
   *   Render array.
   */
  public function front() {
    $banks = $this->entityManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

    $content['tupas_bank_items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tupas-bank-items']],
    ];
    foreach ($banks as $bank) {
      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasFormBase', new TupasService($bank, [
          'language' => 'FI',
          'return_url' => 'tupas_session.return',
          'cancel_url' => 'tupas_session.canceled',
          'rejected_url' => 'tupas_session.return',
          'transaction_id' => random_int(100000, 999999),
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
    $bank = $this->entityManager()
      ->getStorage('tupas_bank')
      ->load($request->query->get('bank_id'));

    if (!$bank instanceof TupasBank) {
      drupal_set_message($this->t('Bank not found'));

      return $this->redirect('<front>');
    }
    $tupas = new TupasService($bank, [
      'transaction_id' => $request->query->get('transaction_id'),
    ]);

    try {
      $tupas->validate($request->query->all());
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('MAC validation failed'), 'error');

      return $this->redirect('<front>');
    }
    // Start tupas session.
    $this->sessionManager->start($request->query->get('transaction_id'), $request->query->get('B02K_CUSTID'));

    // Allow message to be customized.
    $message = $this->eventDispatcher->dispatch(SessionEvents::MESSAGE_ALTER, new MessageAlterEvent($this->t('TUPAS authentication succesful.')));
    // Allow message to be disabled.
    if ($message->getMessage()) {
      drupal_set_message($message->getMessage(), $message->getType());
    }

    // Allow  redirect path to be customized.
    $uri = $this->eventDispatcher->dispatch(SessionEvents::REDIRECT_ALTER, new RedirectAlterEvent('<front>'));

    return $this->redirect($uri->getPath());
  }

  /**
   * Callback for /user/tupas/cancel path.
   */
  public function cancel() {
    drupal_set_message($this->t('TUPAS authentication was canceled by user.'), 'warning');

    return $this->redirect('<front>');
  }

  /**
   * Callback for /user/tupas/rejected path.
   */
  public function rejected() {
    drupal_set_message($this->t('TUPAS authentication was rejected.'), 'warning');

    return $this->redirect('<front>');
  }

}
