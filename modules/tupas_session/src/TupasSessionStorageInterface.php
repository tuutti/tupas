<?php

namespace Drupal\tupas_session;

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
   * @param int $expire
   *   The expiration time.
   * @param array $data
   *   Values to save.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Status of crud operation.
   */
  public function save($expire, array $data);

  /**
   * Delete current session(s).
   */
  public function delete();

}
