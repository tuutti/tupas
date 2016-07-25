<?php
namespace Drupal\tupas_temporary_session\Event;

/**
 * Class TemporarySessionEvents
 *
 * @package Drupal\tupas_temporary_session\Event
 */
final class TemporarySessionEvents {

  /**
   * Allow redirect (succesful tupas authentication) path to be altered.
   *
   * @var string
   */
  const REDIRECT_ALTER = 'tupas_temporary_session.redirect_alter';

  /**
   * Allow redirect (canceled tupas authentication) path to be altered.
   *
   * @var string
   */
  const REDIRECT_CANCEL_ALTER = 'tupas_temporary_session.cancel_redirect_alter';

  /**
   * Allow redirect (rejected tupas authentication) path to be altered.
   *
   * @var string
   */
  const REDIRECT_REJECTED_ALTER = 'tupas_temporary_session.rejected_redirect_alter';

  /**
   * Allow succesfull tupas authentication message to be altered.
   *
   * @var string.
   */
  const MESSAGE_ALTER = 'tupas_temporary_session.message_alter';

  /**
   * Allow canceled tupas authentication message to be altered.
   *
   * @var string.
   */
  const MESSAGE_CANCEL_ALTER = 'tupas_temporary_session.cancel_message_alter';

  /**
   * Allow rejected tupas authentication message to be altered.
   *

   * @var string.
   */
  const MESSAGE_REJECTED_ALTER = 'tupas_temporary_session.rejected_message_alter';
}