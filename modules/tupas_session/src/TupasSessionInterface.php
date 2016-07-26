<?php
namespace Drupal\tupas_session;

use Drupal\user\UserInterface;

interface TupasSessionInterface {

  /**
   * Delete session for given user.
   *
   * @param $uid
   * @return mixed
   */
  public function delete($uid);

  /**
   * Insert/update session for given account.
   *
   * @param \Drupal\user\UserInterface $account
   * @param $transaction_id
   * @param $expiration
   * @return mixed
   */
  public function save(UserInterface $account, $transaction_id, $expiration);

  /**
   * Get session for given uid.
   *
   * @param $uid
   * @return mixed
   */
  public function get($uid);
}