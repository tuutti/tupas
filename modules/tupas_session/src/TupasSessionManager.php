<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\tupas\TupasService;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TupasSessionManager.
 *
 * @package Drupal\tupas_session
 */
class TupasSessionManager implements TupasSessionManagerInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The temporary storage service.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store
   *   The temporary storage service.
   */
  public function __construct(ConfigFactory $config_factory, EntityManagerInterface $entity_manager, EventDispatcherInterface $event_dispatcher, PrivateTempStoreFactory $temp_store) {
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->tempStore = $temp_store;
  }

  /**
   * Return active session if possible.
   *
   * @return mixed
   *   FALSE if no session found, session object if session available.
   */
  public function getSession() {
    if (!$session = $this->tempStore->get('tupas_session')) {
      return FALSE;
    }
    return $session;
  }

  /**
   * {@inheritdoc}
   */
  public function start($transaction_id, $unique_id) {
    $config = $this->configFactory->get('tupas_session.settings');
    $session_length = (int) $config->get('tupas_session_length');
    // Session length defaults to 1 in case session lenght is not enabled.
    // This is to make sure we create one time session that allow us to set
    // tupas_authenticated role later on.
    if (empty($session_length)) {
      $session_length = 1;
    }
    $expire = $session_length * 60 + REQUEST_TIME;

    // Store tupas session.
    $this->tempStore->set('tupas_session', [
      'transaction_id' => $transaction_id,
      'expire' => $expire,
      // Hash social security number.
      'unique_id' => TupasService::hashSsn($unique_id),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    $this->tempStore->delete('tupas_session');
  }

}
