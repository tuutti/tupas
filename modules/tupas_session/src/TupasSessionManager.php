<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas\Exception\TupasGenericException;
use Drupal\tupas\TupasService;
use Drupal\tupas_session\Event\SessionAlterEvent;
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
   * {@inheritdoc}
   */
  public function start($transaction_id, $unique_id) {
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
      $session_data = new SessionAlterEvent($transaction_id, $expire, TupasService::hashSsn($unique_id));
      // Store tupas session.
      $this->tempStore->set('tupas_session', [
        'transaction_id' => $session_data->getTransactionId(),
        'expire' => $session_data->getExpire(),
        'unique_id' => $session_data->getUniqueId(),
        'data' => $session_data->getData(),
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
  public function migrateLoginRegister(SessionAlterEvent $session, array $values) {
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
   * {@inheritdoc}
   */
  public function destroy() {
    return $this->tempStore->delete('tupas_session');
  }

}
