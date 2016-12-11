<?php

namespace Drupal\tupas\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Tupas\Entity\BankInterface;

/**
 * Provides an interface for defining Tupas bank entities.
 */
interface TupasBankInterface extends BankInterface, ConfigEntityInterface {

  /**
   * Get bank status (enabled/disabled).
   *
   * @return bool
   *   Status.
   */
  public function getStatus();

  /**
   * Validate id type.
   *
   * @return bool
   *    TRUE on success, FALSE on failure.
   */
  public function validIdType();

  /**
   * Get list of hashable return codes.
   *
   * @return array
   *   List of return codes.
   */
  public static function getHashableTypes();

}
