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
   * @param int $transaction_id
   *   Transaction id.
   * @param string $unique_id
   *   Unique identifier (SSN).
   */
  public function start($transaction_id, $unique_id);

  /**
   * Destroy tupas session.
   *
   * @return bool
   *   Status of delete event.
   */
  public function destroy();
}