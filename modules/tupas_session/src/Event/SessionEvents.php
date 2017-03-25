<?php

namespace Drupal\tupas_session\Event;

/**
 * Class SessionEvents.
 *
 * @package Drupal\tupas_session\Event
 */
final class SessionEvents {

  /**
   * Allow redirect (succesful tupas authentication) path to be altered.
   *
   * @see \Drupal\tupas_session\Event\RedirectAlterEvent
   * @var string
   */
  const REDIRECT_ALTER = 'tupas_session.redirect_alter';

  /**
   * Allow session storage to be altered.
   *
   * This will be called before saving a session.
   * Please note that session will be saved on every
   * non-cached page request.
   *
   * @see \Drupal\tupas_session\Event\SessionData
   * @var string
   */
  const SESSION_ALTER = 'tupas_session.session_alter';

  /**
   * Allow users to alter session on user logout.
   *
   * @see \Drupal\tupas_session\Event\SessionData
   * @var string
   */
  const SESSION_LOGOUT = 'tupas_session.session.logout';

  /**
   * Allow customer id to be altered.
   *
   * @see \Drupal\tupas_session\Event\CustomerIdAlterEvent
   * @var string
   */
  const CUSTOMER_ID_ALTER = 'tupas_session.customer_id_alter';

  /**
   * Allow custom stuff to be executed on login.
   *
   * @see \Drupal\tupas_session\Event\SessionAuthenticationEvent
   * @var string
   */
  const SESSION_LOGIN = 'tupas_session.login';

  /**
   * Allow custom stuff to be executed on registration.
   *
   * @see \Drupal\tupas_session\Event\SessionAuthenticationEvent
   * @var string
   */
  const SESSION_REGISTER = 'tupas_session.register';

}
