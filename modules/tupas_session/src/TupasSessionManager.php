<?php

namespace Drupal\tupas_session;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\tupas_session\Event\SessionData;
use Drupal\tupas_session\Event\SessionEvents;
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
   * {@inheritdoc}
   */
  public function getSession() {
    if (!$session = $this->storage->get()) {
      return FALSE;
    }
    if (empty($session['data'])) {
      throw new \InvalidArgumentException('Missing required data field.');
    }
    $data = $session['data'];

    if (is_scalar($data)) {
      $data = unserialize($data);
    }
    return new SessionData($data['transaction_id'], $data['unique_id'], $session['expire'], $data['data']);
  }

  /**
   * {@inheritdoc}
   */
  public function renew() {
    // @todo Add some kind of lazy writing method.
    if (!$session = $this->getSession()) {
      return FALSE;
    }
    return $this->start($session->getTransactionId(), $session->getUniqueId(), $session->getData());
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
      $expire = ($expire * 60) + (int) $_SERVER['REQUEST_TIME'];
    }
    // Allow session data to be altered.
    $session_data = new SessionData($transaction_id, $unique_id, $expire, $data);
    /** @var SessionData $session */
    $session = $this->eventDispatcher->dispatch(SessionEvents::SESSION_ALTER, $session_data);
    // Store tupas session.
    return $this->storage->save($session->getExpire(), [
      'transaction_id' => $session->getTransactionId(),
      'unique_id' => $session->getUniqueId(),
      'data' => $session->getData(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function migrate(SessionData $session, callable $callback = NULL) {
    $return = NULL;
    // Destroy current session.
    $this->destroy();
    // Attempt to call given callback. This is usually closure with
    // login / register logic.
    // @todo Refactor this.
    if (is_callable($callback)) {
      $return = $callback($session);
    }
    // Start new session for logged in user.
    $this->start($session->getTransactionId(), $session->getUniqueId(), $session->getData());

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    return $this->storage->delete();
  }

  /**
   * Handle garbage collection.
   */
  public function gc() {
    $this->storage->deleteExpired($_SERVER['REQUEST_TIME']);
  }

  /**
   * Generate unique username for account.
   *
   * @param string $name
   *   Username base.
   *
   * @return string
   *   Unique username.
   */
  public function uniqueName($name = NULL) {
    if (!$name) {
      // @todo Generate human readable username?
      $random = new Random();
      // Generate unique username.
      while (TRUE) {
        $name = $random->string(10);

        if (!user_load_by_name($name)) {
          break;
        }
      }
      return $name;
    }
    $parts = explode(' ', strtolower($name));

    if (isset($parts[1])) {
      // Name is uppercase by default. Convert to lowercase and
      // capitalize first letter.
      list($first, $last) = $parts;

      $name = sprintf('%s %s', ucfirst($first), ucfirst($last));
    }
    $i = 1;
    // Generate unique username, by incrementing suffix.
    while (TRUE) {
      if (!user_load_by_name($name)) {
        break;
      }
      $name = sprintf('%s %d', $name, $i++);
    }
    return $name;
  }

}
