<?php
namespace Drupal\tupas_temporary_session\Event;

/**
 * Class TemporarySessionEvents
 *
 * @package Drupal\tupas_temporary_session\Event
 */
final class TemporarySessionEvents {

  /**
   * @var string
   */
  const RETURN_REDIRECT_ALTER = 'tupas_temporary_session.return_redirect_alter';

  /**
   * @var string.
   */
  const RETURN_MESSAGE_ALTER = 'tupas_temporary_session.return_message_alter';
}