<?php

namespace Drupal\tupas_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CustomerIdAlterEvent.
 *
 * @package Drupal\tupas_session\Event
 */
class CustomerIdAlterEvent extends Event {

  /**
   * Customer id.
   *
   * @var string
   */
  protected $customerId;

  /**
   * Additional data.
   *
   * @var array
   */
  protected $data;

  /**
   * CustomerIdAlterEvent constructor.
   *
   * @param string $customer_id
   *   Default hashed customer id.
   * @param array $data
   *   Contains plain text customer id.
   */
  public function __construct($customer_id, array $data = []) {
    $this->customerId = $customer_id;
    $this->data = $data;
  }

  /**
   * Get customer id.
   *
   * @return string
   *   The customer id.
   */
  public function getCustomerId() {
    return $this->customerId;
  }

  /**
   * Set customer id.
   *
   * @param string $customer_id
   *   The customer id.
   *
   * @return $this
   */
  public function setCustomerId($customer_id) {
    $this->customerId = $customer_id;
    return $this;
  }

  /**
   * Get data.
   *
   * @return array
   *   Data array.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Set data.
   *
   * @param array $data
   *   Data array.
   *
   * @return $this
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

}
