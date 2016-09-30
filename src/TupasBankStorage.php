<?php
namespace Drupal\tupas;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Class TupasBankStorage.
 *
 * @package Drupal\tupas
 */
class TupasBankStorage extends ConfigEntityStorage {

  /**
   * Get all enabled banks.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   List of enabled banks.
   */
  public function getEnabled() {
    return $this->loadByProperties(['status' => 1]);
  }

}
