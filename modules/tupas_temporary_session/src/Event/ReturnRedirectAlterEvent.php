<?php
namespace Drupal\tupas_temporary_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ReturnRedirectAlterEvent
 *
 * @package Drupal\tupas_temporary_session\Event
 */
class ReturnRedirectAlterEvent extends Event {

  /**
   * @var string
   */
  protected $path;

  /**
   * ReturnRedirectAlterEvent constructor.
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
