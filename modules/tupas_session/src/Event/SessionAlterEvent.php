<?php

namespace Drupal\tupas_session\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class SessionAlterEvent.
 *
 * @package Drupal\tupas_session\Event
 */
class SessionAlterEvent extends Event {

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
   * Expiration time.
   *
   * @var int
   */
  protected $expire;

  /**
   * Additional session data.
   *
   * @var array
   */
  protected $data;

  /**
   * SessionAlterEvent constructor.
   *
   * @param int $transaction_id
   *   Transaction id.
   * @param string $unique_id
   *   Unique id for session.
   * @param int $expire
   *   Session expiration.
   * @param array $data
   *   Allow users to store additional data.
   */
  public function __construct($transaction_id, $unique_id, $expire, array $data = []) {
    $this->transactionId = $transaction_id;
    $this->uniqueId = $unique_id;
    $this->expire = $expire;
    $this->data = $data;
  }

  /**
   * Store unique id.
   *
   * @param string $unique_id
   *   Unique id for session.
   *
   * @return $this
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
   * Store expiration time.
   *
   * @param int $expire
   *   Session expiration.
   *
   * @return $this
   */
  public function setExpire($expire) {
    $this->expire = $expire;
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
   * Get expiration time.
   *
   * @return int
   *   Expiration time.
   */
  public function getExpire() {
    return $this->expire;
  }

  /**
   * Get additional session data.
   *
   * @return array
   *   Session data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Create new static object from array.
   *
   * @param array $data
   *   Array of data to populate constructor.
   *
   * @return static
   */
  static public function createFromArray(array $data) {
    return new static($data['transaction_id'], $data['expire'], $data['unique_id'], $data['data']);
  }

}
