<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\TupasService;
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
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $auth;

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
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ConfigFactory $config_factory, PrivateTempStoreFactory $temp_store, SessionManagerInterface $session_manager, ExternalAuthInterface $external_auth, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->sessionManager = $session_manager;
    $this->tempStore = $temp_store->get('tupas_registration');
    $this->auth = $external_auth;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Return active session if possible.
   *
   * @return mixed
   *   FALSE if no session found, session object if session available.
   */
  public function getSession() {
    if (!$session = $this->tempStore->get('tupas_session')) {
      return FALSE;
    }
    return SessionAlterEvent::createFromArray($session);
  }

  /**
   * Automatically renew session.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function renew() {
    if ($session = $this->getSession()) {
      return FALSE;
    }
    $this->start($session->getTransactionId(), $session->getUniqueId());
  }

  /**
   * {@inheritdoc}
   */
  public function start($transaction_id, $unique_id) {
    // Destroy existing sessions before starting new session.
    $this->destroy();

    // Drupal does not start session unless we store something in $_SESSION.
    if (!$this->sessionManager->isStarted() && empty($_SESSION['session_stared'])) {
      $_SESSION['session_stared'] = TRUE;

      $this->sessionManager->start();
    }

    $config = $this->configFactory->get('tupas_session.settings');
    $session_length = (int) $config->get('tupas_session_length');
    // Session length defaults to 1 in case session length is not enabled.
    // This is to make sure we create one time session that allow us to set
    // tupas_authenticated role later.
    if (empty($session_length)) {
      $session_length = 1;
    }
    $expire = $session_length * 60 + REQUEST_TIME;

    try {
      // Allow session data to be altered.
      $session_data = new SessionAlterEvent($transaction_id, TupasService::hashSsn($unique_id), $expire);
      $session = $this->eventDispatcher->dispatch(SessionEvents::SESSION_ALTER, $session_data);
      // Store tupas session.
      $this->tempStore->set('tupas_session', [
        'transaction_id' => $session->getTransactionId(),
        'expire' => $session->getExpire(),
        'unique_id' => $session->getUniqueId(),
        'data' => $session->getData(),
      ]);
    }
    // Hash validation failed.
    catch (TupasGenericException $e) {
      // @todo Do something.
    }
  }

  /**
   * Migrate storage to new account and handle registration.
   *
   * Temp store does not handle session migrations, so we have
   * to do this manually.
   *
   * @param SessionAlterEvent $session
   *   Session storage from anonymous account.
   * @param array $values
   *   Account arguments.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function loginRegister(SessionAlterEvent $session, array $values) {
    if (!isset($values['name'], $values['mail'])) {
      return FALSE;
    }

    if (!$account = $this->auth->loginRegister($session->getUniqueId(), 'tupas_registration')) {
      return FALSE;
    }
    // Delete existing tupas session data.
    $this->destroy();

    // Update account details.
    $account->setUsername($values['name'])
      ->setEmail($values['mail'])
      ->setPassword(empty($values['pass']) ? user_password(20) : $values['pass']);
    $account->save();

    // Start new tupas session for our newly logged-in user.
    $this->start($session->getTransactionId(), $session->getUniqueId());

    return TRUE;
  }

  /**
   * Handle session migration for login event.
   *
   * @param \Drupal\tupas_session\Event\SessionAlterEvent $session
   *   Session storage from anonymous account.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function login(SessionAlterEvent $session) {
    // Delete existing session data.
    $this->destroy();

    if ($this->auth->login($session->getUniqueId(), 'tupas_registration')) {
      $this->start($session->getTransactionId(), $session->getUniqueId());

      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    return $this->tempStore->delete('tupas_session');
  }

}
