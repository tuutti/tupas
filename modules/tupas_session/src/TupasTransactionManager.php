<?php

namespace Drupal\tupas_session;

use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

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
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $storage
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
    // @todo This might cause some issues with logged in users.
    $this->sessionManager->regenerate();

    $transaction_id = (string) random_int(100000, 999999);
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
