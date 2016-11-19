<?php

namespace Drupal\tupas_session;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tupas_session\Event\SessionData;
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
   * {@inheritdoc}
   */
  public function save(SessionData $session) {
    $data = $session->getData();

    if (!is_scalar($data)) {
      $data = serialize($data);
    }
    return $this->connection->merge('tupas_session')
      ->keys([
        'unique_id' => $session->getUniqueId(),
      ])
      ->fields([
        'owner' => $this->getOwner(),
        'access' => $session->getAccess(),
        'transaction_id' => $session->getTransactionId(),
        'unique_id' => $session->getUniqueId(),
        'data' => $data,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->connection->delete('tupas_session')
      ->condition('owner', $this->getOwner())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    try {
      $session = $this->connection->select('tupas_session', 's')
        ->fields('s')
        ->condition('owner', $this->getOwner())
        ->range(0, 1)
        ->execute()
        ->fetchObject();

      if (!$session) {
        return FALSE;
      }
      $data = unserialize($session->data);

      if (empty($data)) {
        $data = [];
      }
      return new SessionData($session->transaction_id, $session->unique_id, $session->access, $data);
    }
    catch (\RuntimeException $e) {
      return FALSE;
    }
  }

  /**
   * Gets the current owner based on the current user or the session ID.
   *
   * @return string
   *   The owner.
   */
  protected function getOwner() {
    // Session is not guaranteed to be active. Throw an exception to
    // exit gracefully.
    // @see #2828252.
    if (!$this->requestStack->getCurrentRequest()->getSession() && !$this->currentUser->id()) {
      throw new \RuntimeException('Session not initialized.');
    }
    return $this->currentUser->id() ?: $this->requestStack->getCurrentRequest()->getSession()->getId();
  }

  /**
   * Delete expired sessions.
   *
   * @param int $before
   *   Timestamp before to delete sessions.
   *
   * @return int
   *   Status of delete operation.
   */
  public function deleteExpired($before) {
    return $this->connection->delete('tupas_session')
      ->condition('access', 0, '!=')
      ->condition('access', $before, '<')
      ->execute();
  }

}
