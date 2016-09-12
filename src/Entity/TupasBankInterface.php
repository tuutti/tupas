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
   *   Status.
   */
  public function getStatus();

  /**
   * Get bank action url.
   *
   * @return string
   *   Action url.
   */
  public function getActionUrl();

  /**
   * Get bank cert version.
   *
   * @return string.
   *   Cert version.
   */
  public function getCertVersion();

  /**
   * Get receiver id.
   *
   * @return string
   *   Rcv id.
   */
  public function getRcvId();

  /**
   * Get receiver key.
   *
   * @return string
   *   Rcv key.
   */
  public function getRcvKey();

  /**
   * Get key version.
   *
   * @return string
   *   Key version.
   */
  public function getKeyVersion();

  /**
   * Get encryption algorithm.
   *
   * @return string
   *   Encryption alg.
   */
  public function getEncryptionAlg();

  /**
   * Get id type of bank.
   *
   * @return string
   *   Id type.
   */
  public function getIdType();

}
