<?php

namespace Drupal\tupas\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Tupas bank entities.
 */
interface TupasBankInterface extends ConfigEntityInterface {

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

  /**
   * Gets the language code.
   *
   * @return string
   *   The language.
   */
  public function getLanguage();

  /**
   * Gets the return url.
   *
   * @return mixed
   *   The return url.
   */
  public function getReturnUrl();

  /**
   * Gets the cancellation url.
   *
   * @return mixed
   *   The cancel url.
   */
  public function getCancelUrl();

  /**
   * Gets the rejected url.
   *
   * @return mixed
   *   The rejected url.
   */
  public function getRejectedUrl();

  /**
   * Gets the transaction id.
   *
   * @return int
   *   The transaction id.
   */
  public function getTransactionId();

  /**
   * Gets the default settings.
   *
   * @return array
   *   List of default settings.
   */
  public function getDefaultSettings();

}
