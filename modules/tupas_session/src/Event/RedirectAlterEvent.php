<?php

namespace Drupal\tupas_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class RedirectAlterEvent.
 *
 * @package Drupal\tupas_session\Event
 */
class RedirectAlterEvent extends Event {

  /**
   * Redirect path.
   *
   * @var string
   */
  protected $path;

  /**
   * The message to show before redirect.
   *
   * @var string
   */
  protected $message;

  /**
   * The url arguments.
   *
   * @var array
   */
  protected $arguments;

  /**
   * RedirectAlterEvent constructor.
   *
   * @param string $path
   *   Path to redirect to.
   * @param array $arguments
   *   The url arguments.
   * @param string $message
   *   The message.
   */
  public function __construct($path, array $arguments = [], $message = NULL) {
    $this->path = $path;
    $this->arguments = $arguments;
    $this->message = $message;
  }

  /**
   * Set arguments.
   *
   * @param array $arguments
   *   The url arguments.
   *
   * @return $this
   */
  public function setArguments($arguments) {
    $this->arguments = $arguments;
    return $this;
  }

  /**
   * Gets url arguments.
   *
   * @return array
   *   The url arguments.
   */
  public function getArguments() {
    return $this->arguments;
  }

  /**
   * Set message.
   *
   * @param string $message
   *   The message.
   *
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
   *   The message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Set path.
   *
   * @param string $path
   *   Path to redirect to.
   *
   * @return $this
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * Get path.
   *
   * @return mixed
   *   Current redirect path.
   */
  public function getPath() {
    return $this->path;
  }

}
