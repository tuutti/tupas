<?php

namespace Drupal\tupas;

/**
 * Interface TupasServiceInterface.
 *
 * @package Drupal\tupas
 */
interface TupasServiceInterface {

  /**
   * Get bank property.
   *
   * @return \Drupal\tupas\Entity\TupasBankInterface
   */
  public function getBank();

  /**
   * Get language code.
   *
   * @return string
   */
  public function getLanguage();

  /**
   * Get return url.
   *
   * @return mixed
   */
  public function getReturnUrl();

  /**
   * Get cancellation url.
   *
   * @return mixed
   */
  public function getCancelUrl();

  /**
   * Get rejected url.
   *
   * @return mixed
   */
  public function getRejectedUrl();

  /**
   * Get transaction id.
   *
   * @return int
   */
  public function getTransactionId();

  /**
   * Get default settings.
   *
   * @return array
   */
  public function getDefaults();

}
