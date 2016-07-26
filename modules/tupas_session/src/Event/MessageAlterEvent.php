<?php
namespace Drupal\tupas_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MessageAlterEvent
 *
 * @package Drupal\tupas_session\Event
 */
class MessageAlterEvent extends Event {

  /**
   * @var string
   */
  protected $message;

  /**
   * MessageAlterEvent constructor.
   *
   * @param $message
   */
  public function __construct($message) {
    $this->message = $message;
  }

  /**
   * Set message.
   *
   * @param $message
   * @return $this
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

  /**
   * Get message.
   *
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }
}