<?php
namespace Drupal\tupas_session;

/**
 * Interface TupasSessionManagerInterface
 *
 * @package Drupal\tupas_session
 */
interface TupasSessionManagerInterface {

  /**
   * Load account for given uid if session exists.
   *
   * @param $uid
   * @return mixed
   */
  public function load($uid);

  /**
   * Start tupas session.
   *
   * @param $uid
   * @param $transaction_id
   * @return mixed
   */
  public function start($uid, $transaction_id);

  /**
   * Destroy tupas session.
   *
   * @param $uid
   * @return mixed
   */
  public function destroy($uid);
}