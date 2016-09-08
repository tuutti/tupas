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
   * RedirectAlterEvent constructor.
   *
   * @param string $path
   *   Path to redirect to.
   */
  public function __construct($path) {
    $this->path = $path;
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
