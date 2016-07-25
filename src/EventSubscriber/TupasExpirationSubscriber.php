<?php

namespace Drupal\tupas\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tupas\TupasServiceInterface;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class TupasExpirationSubscriber.
 *
 * @package Drupal\tupas
 */
class TupasExpirationSubscriber implements EventSubscriberInterface {

  /**
   * @var mixed
   */
  protected $tempStore;

  /**
   * @var \Drupal\tupas\TupasServiceInterface
   */
  protected $tupas;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;


  /**
   * Constructor.
   *
   * @param \Drupal\tupas\TupasServiceInterface $tupas
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(TupasServiceInterface $tupas, PrivateTempStoreFactory $temp_store_factory, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->tupas = $tupas;
    $this->tempStore = $temp_store_factory->get('tupas');
    $this->currentUser = $current_user;
    $this->config = $config_factory->get('tupas.settings');
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
    $account->removeRole('tupas_authenticated_user')
      ->save();
  }

}
