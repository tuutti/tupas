<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManagerInterface;
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

    $expire = (int) $config->get('tupas_session_length') * 60 + REQUEST_TIME;

    $this->tempStore->set([
      'transaction_id' => $transaction_id,
      'expire' => $expire,
      'unique_id' => $unique_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function destroy() {
    $this->tempStore->delete('tupas_session');
  }

}
