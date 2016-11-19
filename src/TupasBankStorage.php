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

  /**
   * Load bank by bank number.
   *
   * @param int $bank_number
   *   The bank number.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Bank entity when available, NULL when not available.
   */
  public function loadByBankNumber($bank_number) {
    $bank = $this->loadByProperties(['bank_number' => $bank_number]);

    return $bank ? reset($bank) : NULL;
  }

}
