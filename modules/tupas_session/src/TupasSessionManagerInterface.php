<?php
namespace Drupal\tupas_session;

/**
 * Interface TupasSessionManagerInterface.
 *
 * @package Drupal\tupas_session
 */
interface TupasSessionManagerInterface {

  /**
   * Start tupas session.
   *
   * @param $transaction_id
   * @param $unique_id
   * @return mixed
   */
  public function start($transaction_id, $unique_id);

  /**
   * Destroy tupas session.
   *
   * @return mixed
   */
  public function destroy();
}