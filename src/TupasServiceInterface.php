<?php

namespace Drupal\tupas;

/**
 * Interface for Tupas Services.
 */
interface TupasServiceInterface {

  /**
   * Gets the bank property.
   *
   * @return \Drupal\tupas\Entity\TupasBankInterface
   */
  public function getBank();

  /**
   * Gets the language code.
   *
   * @return string
   */
  public function getLanguage();

  /**
   * Gets the return url.
   *
   * @return mixed
   */
  public function getReturnUrl();

  /**
   * Gets the cancellation url.
   *
   * @return mixed
   */
  public function getCancelUrl();

  /**
   * Gets the rejected url.
   *
   * @return mixed
   */
  public function getRejectedUrl();

  /**
   * Gets the transaction id.
   *
   * @return int
   */
  public function getTransactionId();

  /**
   * Gets the default settings.
   *
   * @return array
   */
  public function getDefaults();

}
