<?php

namespace Drupal\tupas;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TupasPrivateTempStoreFactory.
 *
 * @package Drupal\tupas
 */
class TupasPrivateTempStoreFactory extends PrivateTempStoreFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(KeyValueExpirableFactory $keyvalue_expirable, DatabaseLockBackend $lock, AccountProxy $current_user, RequestStack $request_stack, $expire = 604800, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('tupas.settings');

    // Override default expire.
    // @todo Is there easier way to do this?
    if (!empty($config->get('tupas_session_length'))) {
      $expire = REQUEST_TIME + (60 * $config->get('tupas_session_length'));
    }
    parent::__construct($keyvalue_expirable, $lock, $current_user, $request_stack, $expire);
  }

}
