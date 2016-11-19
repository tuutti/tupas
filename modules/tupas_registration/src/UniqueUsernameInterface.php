<?php

namespace Drupal\tupas_registration;

/**
 * Interface UniqueUsernameInterface.
 *
 * @package Drupal\tupas_registration
 */
interface UniqueUsernameInterface {

  /**
   * Load user by username.
   *
   * @param string $name
   *   The username.
   *
   * @return bool
   *   TRUE if user exists, FALSE if not.
   */
  public function userExists($name);

  /**
   * Generate unique username for account.
   *
   * @param string $name
   *   Username base.
   *
   * @return string
   *   Unique username.
   */
  public function getName($name = NULL);

}