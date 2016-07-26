<?php

namespace Drupal\tupas_session\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class TupasExpirationSubscriber.
 *
 * @package Drupal\tupas_session
 */
class TupasExpirationSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Constructor.
   *
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(TupasSessionManagerInterface $session_manager, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->config = $config_factory->get('tupas_session.settings');
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.request'] = ['checkExpiration'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function checkExpiration(GetResponseEvent $event) {
    $account = $this->currentUser->getAccount();

    if (empty($this->config->get('tupas_session_length')) || !$account->isAuthenticated()) {
      return;
    }
    $account = User::load($account->id());
    // If user does not have tupas authenticated role or temp storage exists,
    // we can ignore this safely.
    if (!$account->hasRole('tupas_authenticated_user') || $this->tempStore->get('tupas_session_active')) {
      return;
    }
  }

}
