<?php

namespace Drupal\tupas_session;

use Drupal\tupas_session\Event\SessionAlterEvent;

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
   *   Unique identifier.
   */
  public function start($transaction_id, $unique_id);

  /**
   * Migrate session to new user.
   *
   * @param \Drupal\tupas_session\Event\SessionAlterEvent $session
   *   Session from previous user.
   * @param callable $callback
   *   Allow users to call function after session migrate.
   *
   * @return mixed
   *   Status of callback result.
   */
  public function migrate(SessionAlterEvent $session, callable $callback = NULL);

  /**
   * Return active session if possible.
   *
   * @return mixed
   *   FALSE if no session found, session object if session available.
   */
  public function getSession();

  /**
   * Destroy tupas session.
   *
   * @param bool $logout
   *   Whether to log current user out or not.
   *
   * @return bool Status of delete event.
   *   Status of delete event.
   */
  public function destroy($logout = FALSE);

  /**
   * Automatically renew session.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function renew();

}
