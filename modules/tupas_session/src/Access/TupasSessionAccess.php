<?php

namespace Drupal\tupas_session\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tupas_session\TupasSessionManagerInterface;

/**
 * Class TupasSessionAccess.
 *
 * @package Drupal\tupas_session
 */
class TupasSessionAccess implements AccessInterface {

  /**
   * The tupas session manager.
   *
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * TupasSessionAccess constructor.
   *
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager.
   */
  public function __construct(TupasSessionManagerInterface $session_manager) {
    $this->sessionManager = $session_manager;
  }

  /**
   * Check if user has an active tupas session.
   *
   * This can be used with *.routing.yml to check if user has access
   * to given route by adding the following requirement:
   *
   * @code
   *   requirements:
   *     _require_tupas_session: 'TRUE'
   * @endcode
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   TRUE user has an active tupas sesssion, FALSE if not.
   */
  public function access(AccountInterface $account) {
    $result = $this->sessionManager->getSession() ? AccessResult::allowed() : AccessResult::forbidden();
    // Not cacheable.
    return $result->setCacheMaxAge(0);
  }

}
