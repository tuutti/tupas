<?php

namespace Drupal\tupas_session;

use Drupal\Core\Database\Connection;
use Drupal\user\UserInterface;

/**
 * Class TupasSessionService.
 *
 * @package Drupal\tupas_session
 */
class TupasSessionService {

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
    $this->connection->delete('tupas_session')
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function save(UserInterface $account, $transaction_id, $expiration) {
    $this->connection->merge('tupas_session')
      ->keys([
        'uid' => $account->id(),
        'transaction_id' => $transaction_id,
      ])
      ->fields([
        'tupas_expiration_timestamp' => $expiration,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get($uid) {
    $session = $this->connection->select('tupas_session', 'ts')
      ->fields('ts', ['tupas_session'])
      ->condition('uid', $uid)
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if ($session) {
      return $session;
    }
    return FALSE;
  }

}
