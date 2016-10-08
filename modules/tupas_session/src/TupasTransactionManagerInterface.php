<?php

namespace Drupal\tupas_session;

/**
 * Interface TupasTransactionManagerInterface.
 *
 * @package Drupal\tupas_session
 */
interface TupasTransactionManagerInterface {

  /**
   * Regenerate current transaction.
   */
  public function regenerate();

  /**
   * Get transaction id.
   *
   * @return int
   *   The transaction id.
   */
  public function get();

  /**
   * Gelete transaction id after used.
   */
  public function delete();

}
