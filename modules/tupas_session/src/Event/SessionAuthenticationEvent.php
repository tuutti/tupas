<?php

namespace Drupal\tupas_session\Event;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class SessionAuthenticationEvent.
 */
final class SessionAuthenticationEvent extends Event {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The current tupas session.
   *
   * @var \Drupal\tupas_session\Event\SessionData
   */
  protected $session;

  /**
   * SessionAuthenticationEvent constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\tupas_session\Event\SessionData $session_data
   *   The current tupas session.
   */
  public function __construct(AccountInterface $account, SessionData $session_data) {
    $this->account = $account;
    $this->session = $session_data;
  }

  /**
   * Gets current account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets current session.
   *
   * @return \Drupal\tupas_session\Event\SessionData
   *   The tupas session.
   */
  public function getSession() {
    return $this->session;
  }

}
