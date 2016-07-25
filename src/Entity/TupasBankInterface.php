<?php

namespace Drupal\tupas\Entity;

/**
 * Provides an interface for defining Tupas bank entities.
 */
interface TupasBankInterface {

  /**
   * Get bank status (enabled/disabled).
   *
   * @return bool
   */
  public function getStatus();

  /**
   * Get bank action url.
   *
   * @return string
   */
  public function getActionUrl();

  /**
   * Get bank cert version.
   *
   * @return string.
   */
  public function getCertVersion();

  /**
   * Get receiver id.
   *
   * @return string
   */
  public function getRcvId();

  /**
   * Get receiver key.
   *
   * @return string
   */
  public function getRcvKey();

  /**
   * Get key version.
   *
   * @return string
   */
  public function getKeyVersion();

  /**
   * Get encryption algorithm.
   *
   * @return string
   */
  public function getEncryptionAlg();
}
