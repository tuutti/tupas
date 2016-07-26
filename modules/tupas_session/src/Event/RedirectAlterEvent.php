<?php
namespace Drupal\tupas_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class RedirectAlterEvent
 *
 * @package Drupal\tupas_session\Event
 */
class RedirectAlterEvent extends Event {

  /**
   * @var string
   */
  protected $path;

  /**
   * RedirectAlterEvent constructor.
   *
   * @param $path
   */
  public function __construct($path) {
    $this->path = $path;
  }

  /**
   * Set path.
   *
   * @param $path
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
   */
  public function getPath() {
    return $this->path;
  }
}
