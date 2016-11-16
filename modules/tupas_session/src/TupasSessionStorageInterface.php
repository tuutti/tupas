<?php

namespace Drupal\tupas_session;

use Drupal\tupas_session\Event\SessionData;

/**
 * Interface TupasSessionStorageInterface.
 *
 * @package Drupal\tupas_session
 */
interface TupasSessionStorageInterface {

  /**
   * Get session for active user or session.
   *
   * @return mixed
   *   Session object on success, FALSE on failure.
   */
  public function get();

  /**
   * Save values to database.
   *
   * @param \Drupal\tupas_session\Event\SessionData $session
   *   The session.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Status of crud operation.
   */
  public function save(SessionData $session);

  /**
   * Delete current session(s).
   */
  public function delete();

}
