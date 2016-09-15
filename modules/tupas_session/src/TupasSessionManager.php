<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\tupas_session\Event\SessionAlterEvent;
use Drupal\tupas_session\Event\SessionEvents;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TupasSessionManager.
 *
 * @package Drupal\tupas_session
 */
class TupasSessionManager implements TupasSessionManagerInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The temporary storage service.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * The session manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store
   *   The temporary storage service.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Session manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ConfigFactory $config_factory, PrivateTempStoreFactory $temp_store, SessionManagerInterface $session_manager, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->sessionManager = $session_manager;
    $this->tempStore = $temp_store->get('tupas_registration');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getSession() {
    if (!$session = $this->tempStore->get('tupas_session')) {
      return FALSE;
    }
    return SessionAlterEvent::createFromArray($session);
  }

  /**
   * {@inheritdoc}
   */
  public function renew() {
    // @todo Add some kind of lazy writing method.
    if (!$session = $this->getSession()) {
      return FALSE;
    }
    $this->start($session->getTransactionId(), $session->getUniqueId());
  }

  /**
   * {@inheritdoc}
   */
  public function start($transaction_id, $unique_id, array $data = []) {
    // Drupal does not start session unless we store something in $_SESSION.
    if (!$this->sessionManager->isStarted() && empty($_SESSION['session_stared'])) {
      $_SESSION['session_stared'] = TRUE;

      $this->sessionManager->start();
    }

    $config = $this->configFactory->get('tupas_session.settings');
    $expire = (int) $config->get('tupas_session_length');

    // Set session length only if configured.
    if ($expire > 0) {
      $expire = $expire * 60 + REQUEST_TIME;
    }
    // Allow session data to be altered.
    $session_data = new SessionAlterEvent($transaction_id, $unique_id, $expire, $data);
    $session = $this->eventDispatcher->dispatch(SessionEvents::SESSION_ALTER, $session_data);
    // Store tupas session.
    $this->tempStore->set('tupas_session', [
      'transaction_id' => $session->getTransactionId(),
      'expire' => $session->getExpire(),
      'unique_id' => $session->getUniqueId(),
      'data' => $session->getData(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function migrate(SessionAlterEvent $session, callable $callback = NULL) {
    $return = NULL;
    // Destroy current session.
    $this->destroy();
    // Attempt to call given callback. This is usually closure with
    // login / register logic.
    if (is_callable($callback)) {
      $return = $callback($session);
    }
    // Start new session for logged in user.
    $this->start($session->getTransactionId(), $session->getUniqueId());

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy($logout = FALSE) {
    $status = $this->tempStore->delete('tupas_session');

    if ($logout) {
      user_logout();
    }
    return $status;
  }

}
