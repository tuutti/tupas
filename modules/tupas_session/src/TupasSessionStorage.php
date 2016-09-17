<?php

namespace Drupal\tupas_session;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TupasSessionStorage.
 *
 * @package Drupal\tupas_session
 */
class TupasSessionStorage implements TupasSessionStorageInterface {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(Connection $connection, AccountProxyInterface $current_user, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * Save values to database.
   *
   * @param int $expire
   *   The expiration time.
   * @param array $data
   *   Values to save.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Status of crud operation.
   */
  public function save($expire, array $data) {
    if (!is_scalar($data)) {
      $data = serialize($data);
    }
    return $this->connection->merge('tupas_session')
      ->keys([
        'expire' => $expire,
        'owner' => $this->getOwner(),
      ])
      ->fields([
        'data' => $data,
      ])
      ->execute();
  }

  /**
   * Delete current session.
   */
  public function delete() {
    $this->connection->delete('tupas_session')
      ->condition('owner', $this->getOwner())
      ->execute();
  }

  /**
   * Get session for active user or session.
   *
   * @return mixed
   *   Session object on success, FALSE on failure.
   */
  public function get() {
    $session = $this->connection->select('tupas_session', 's')
      ->fields('s')
      ->condition('owner', $this->getOwner())
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    return $session ?: FALSE;
  }

  /**
   * Gets the current owner based on the current user or the session ID.
   *
   * @return string
   *   The owner.
   */
  protected function getOwner() {
    return $this->currentUser->id() ?: $this->requestStack->getCurrentRequest()->getSession()->getId();
  }

}
