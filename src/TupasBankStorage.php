<?php
namespace Drupal\tupas;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

class TupasBankStorage extends ConfigEntityStorage {

  /**
   * Get all enabled banks.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   */
  public function getEnabled() {
    return $this->loadByProperties(['status' => 1]);
  }

}
