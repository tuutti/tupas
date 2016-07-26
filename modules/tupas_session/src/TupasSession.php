<?php

namespace Drupal\tupas_session;

use Drupal\Core\Database\Connection;
use Drupal\user\UserInterface;

/**
 * Class TupasSession.
 *
 * @package Drupal\tupas_session
 */
class TupasSession {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connection;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($uid) {
    $this->connection->delete('tupas_sessions')
      ->condition('user_id', $uid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(UserInterface $account, $transaction_id, $expiration) {
    $this->connection->merge('tupas_sessions')
      ->keys([
        'user_id' => $account->id(),
      ])
      ->fields([
        'tupas_expiration_timestamp' => $expiration,
        'transaction_id' => $transaction_id,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get($uid) {
    $session = $this->connection->select('tupas_sessions', 'ts')
      ->fields('ts')
      ->condition('user_id', $uid)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($session) {
      return $session;
    }
    return FALSE;
  }

}
