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

}
