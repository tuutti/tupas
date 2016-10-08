<?php

namespace Drupal\tupas_session;

use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Class TupasTransactionManager.
 *
 * @package Drupal\tupas_session
 */
class TupasTransactionManager implements TupasTransactionManagerInterface {

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The private temp store.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $storage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Drupal\user\PrivateTempStoreFactory $storage
   *   The private tempstore factory.
   */
  public function __construct(SessionManagerInterface $session_manager, PrivateTempStoreFactory $storage) {
    $this->sessionManager = $session_manager;
    $this->storage = $storage->get('tupas_session');
  }

  /**
   * {@inheritdoc}
   */
  public function regenerate() {
    // We need to start session manually for private temp storage.
    if (!$this->sessionManager->isStarted() && empty($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;

      $this->sessionManager->start();
    }
    $transaction_id = random_int(100000, 999999);
    // Store transaction id in temporary storage.
    $this->storage->set('transaction_id', $transaction_id);

    return $transaction_id;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->storage->get('transaction_id');
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->storage->delete('transaction_id');
  }

}
