<?php

namespace Drupal\tupas_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class SessionData.
 *
 * @package Drupal\tupas_session\Event
 */
class SessionData extends Event {

  /**
   * Unique id for session.
   *
   * @var string
   */
  protected $uniqueId;

  /**
   * Transaction id.
   *
   * @var int
   */
  protected $transactionId;

  /**
   * Access time.
   *
   * @var int
   */
  protected $access;

  /**
   * Additional session data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * SessionData constructor.
   *
   * @param int $transaction_id
   *   Transaction id.
   * @param string $unique_id
   *   Unique id for session.
   * @param int $access
   *   Session last access time.
   * @param array $data
   *   Allow users to store additional data.
   */
  public function __construct($transaction_id, $unique_id, $access, array $data = []) {
    $this->transactionId = $transaction_id;
    $this->uniqueId = $unique_id;
    $this->access = $access;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setUniqueId($unique_id) {
    $this->uniqueId = $unique_id;
    return $this;
  }

  /**
   * Store transactioni d.
   *
   * @param int $transaction_id
   *   Transaction id.
   *
   * @return $this
   */
  public function setTransactionId($transaction_id) {
    $this->transactionId = $transaction_id;
    return $this;
  }

  /**
   * Store access time.
   *
   * @param int $access
   *   Session last access time.
   *
   * @return $this
   */
  public function setAccess($access) {
    $this->access = $access;
    return $this;
  }

  /**
   * Store additional session data.
   *
   * @param array $data
   *   Session data.
   *
   * @return $this
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * Get unique id.
   *
   * @return string
   *   Unique id.
   */
  public function getUniqueId() {
    return $this->uniqueId;
  }

  /**
   * Get transaction id.
   *
   * @return int
   *   Transaction id.
   */
  public function getTransactionId() {
    return $this->transactionId;
  }

  /**
   * Get access time.
   *
   * @return int
   *   The last access time.
   */
  public function getAccess() {
    return $this->access;
  }

  /**
   * Get additional session data.
   *
   * @param string $key
   *   Fetch item by key.
   *
   * @return array
   *   Session data.
   */
  public function getData($key = NULL) {
    if ($key) {
      return isset($this->data[$key]) ? $this->data[$key] : NULL;
    }
    return $this->data;
  }

}
