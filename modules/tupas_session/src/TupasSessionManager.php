<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas_session\Event\SessionData;
use Drupal\tupas_session\Event\SessionEvents;
use Drupal\user\UserInterface;
use Drupal\tupas_session\Event\SessionAuthenticationEvent;
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
   * The session storage controller.
   *
   * @var \Drupal\tupas_session\TupasSessionStorage
   */
  protected $storage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\tupas_session\TupasSessionStorageInterface $session_storage
   *   The session storage controller.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Session manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TupasSessionStorageInterface $session_storage, SessionManagerInterface $session_manager, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->sessionManager = $session_manager;
    $this->storage = $session_storage;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Get request time.
   *
   * @todo Replace with time service in 8.3.x.
   *
   * @return int
   *   The request time.
   */
  public function getTime() {
    return (int) $_SERVER['REQUEST_TIME'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSession() {
    if (!$session = $this->storage->get()) {
      return FALSE;
    }
    return $session;
  }

  /**
   * {@inheritdoc}
   */
  public function renew() {
    if (!$session = $this->getSession()) {
      return FALSE;
    }
    $session->setAccess($this->getTime());
    return $this->storage->save($session);
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    return $this->configFactory
      ->get('tupas_session.settings')
      ->get($key);
  }

  /**
   * Create wrapper to save data to $_SESSION.
   *
   * This is used to prevent unit tests from being marked as a risky.
   *
   * @todo Refactor.
   */
  public function startNativeSession() {
    // Drupal does not start session unless we store something in $_SESSION.
    $_SESSION['tupas_session'] = TRUE;

    $this->sessionManager->start();
  }

  /**
   * {@inheritdoc}
   */
  public function start($transaction_id, $unique_id, array $data = []) {
    $this->startNativeSession();

    // Allow session data to be altered.
    $session_data = new SessionData($transaction_id, $unique_id, $this->getTime(), $data);
    /** @var \Drupal\tupas_session\Event\SessionData $session */
    $session = $this->eventDispatcher->dispatch(SessionEvents::SESSION_ALTER, $session_data);

    return $this->storage->save($session);
  }

  /**
   * {@inheritdoc}
   */
  public function recreate(SessionData $session) {
    $this->start($session->getTransactionId(), $session->getUniqueId(), $session->getData());

    return $this->getSession();
  }

  /**
   * {@inheritdoc}
   */
  public function login(ExternalAuthInterface $auth) {
    if (!$session = $this->getSession()) {
      return FALSE;
    }
    // Login before migrating session over.
    if (!$account = $auth->login($session->getUniqueId(), 'tupas_registration')) {
      return FALSE;
    }
    $this->recreate($session);

    $this->eventDispatcher->dispatch(SessionEvents::SESSION_LOGIN, new SessionAuthenticationEvent($account, $session));

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function linkExisting(ExternalAuthInterface $auth, UserInterface $account) {
    if (!$session = $this->getSession()) {
      return FALSE;
    }
    $auth->linkExistingAccount($session->getUniqueId(), 'tupas_registration', $account);
    $this->recreate($session);

    $this->eventDispatcher->dispatch(SessionEvents::SESSION_REGISTER, new SessionAuthenticationEvent($account, $session));

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function loginRegister(ExternalAuthInterface $auth, array $data = []) {
    if (!$session = $this->getSession()) {
      return FALSE;
    }
    // Login before migrating session over.
    if (!$account = $auth->loginRegister($session->getUniqueId(), 'tupas_registration', $data)) {
      return FALSE;
    }
    $this->recreate($session);

    $this->eventDispatcher->dispatch(SessionEvents::SESSION_REGISTER, new SessionAuthenticationEvent($account, $session));

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    if ($session_data = $this->getSession()) {
      $this->eventDispatcher->dispatch(SessionEvents::SESSION_LOGOUT, $session_data);
    }
    return $this->storage->delete();
  }

  /**
   * Handle garbage collection.
   *
   * @param int $timestamp
   *   The expiration timestamp.
   */
  public function gc($timestamp) {
    $this->storage->deleteExpired($timestamp);
  }

}
