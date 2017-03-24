<?php

namespace Drupal\tupas_session;

use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas_session\Event\SessionData;
use Drupal\user\UserInterface;

/**
 * Interface TupasSessionManagerInterface.
 *
 * @package Drupal\tupas_session
 */
interface TupasSessionManagerInterface {

  /**
   * Helper function to get tupas session settings.
   *
   * @param string $key
   *   The setting key.
   *
   * @return mixed
   *   NULL if setting does not exists, setting value if setting exists.
   */
  public function getSetting($key);

  /**
   * Start tupas session.
   *
   * @param int $transaction_id
   *   Transaction id.
   * @param string $unique_id
   *   Unique identifier.
   * @param array $data
   *   Optional session data.
   */
  public function start($transaction_id, $unique_id, array $data = []);

  /**
   * Login wrapper to migrate session over to newly logged in user.
   *
   * Note: the ExternalAuthInterface is injected to the function
   * because we don't want to create a hard dependency to Tupas registration
   * module.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $auth
   *   The external auth service.
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   *
   * @return \Drupal\user\UserInterface|bool
   *   The logged in Drupal user.
   */
  public function linkExisting(ExternalAuthInterface $auth, UserInterface $account);

  /**
   * Login wrapper to migrate session over to newly logged in user.
   *
   * Note: the ExternalAuthInterface is injected to the function
   * because we don't want to create a hard dependency to Tupas registration
   * module.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $auth
   *   The external auth service.
   *
   * @return \Drupal\user\UserInterface|bool
   *   The logged in Drupal user.
   */
  public function login(ExternalAuthInterface $auth);

  /**
   * Login register wrapper to migrate session over to newly logged in user.
   *
   * Note: the ExternalAuthInterface is injected to the function
   * because we don't want to create a hard dependency to Tupas registration
   * module.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $auth
   *   The external auth service.
   * @param array $data
   *   An additional account data.
   *
   * @return bool|\Drupal\user\UserInterface
   *   The logged in Drupal user.
   */
  public function loginRegister(ExternalAuthInterface $auth, array $data = []);

  /**
   * Recreate session with previous session object.
   *
   * @param \Drupal\tupas_session\Event\SessionData $session
   *   The session.
   *
   * @return mixed
   *   TRUE or FALSE depending on if session start succeed.
   */
  public function recreate(SessionData $session);

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
   * @return bool Status of delete event.
   *   Status of delete event.
   */
  public function destroy();

  /**
   * Automatically renew session by updating last access timestamp.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function renew();

}
