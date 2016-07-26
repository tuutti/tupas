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
   * @var string
   */
  protected $type;

  /**
   * MessageAlterEvent constructor.
   *
   * @param $message
   * @param string $type
   */
  public function __construct($message, $type = 'status') {
    $this->message = $message;
    $this->type = $type;
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

  /**
   * Set message type.
   *
   * @param $type
   * @return $this
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * Get message type.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }
}