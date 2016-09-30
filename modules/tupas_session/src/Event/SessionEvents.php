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
   * @var string
   */
  const REDIRECT_ALTER = 'tupas_session.redirect_alter';

  /**
   * Allow succesfull tupas authentication message to be altered.
   *
   * @var string
   */
  const MESSAGE_ALTER = 'tupas_session.message_alter';

  /**
   * Allow session storage to be altered.
   *
   * @var string
   */
  const SESSION_ALTER = 'tupas_session.session_alter';

}
