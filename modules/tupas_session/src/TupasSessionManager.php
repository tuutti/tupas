<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * Tupas session service.
   *
   * @var \Drupal\tupas_session\TupasSession
   */
  protected $tupasSession;

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
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\tupas_session\TupasSession $tupas_session
   *   Tupas session.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactory $config_factory, TupasSession $tupas_session, EntityManagerInterface $entity_manager, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->tupasSession = $tupas_session;
    $this->entityManager = $entity_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function load($uid) {
    if ($session = $this->tupasSession->get($uid)) {
      return $this->entityManager->getStorage('user')->load($uid);
    }
    return FALSE;
  }

  /**
   * @param $uid
   * @return bool|void
   */
  public function getSession($uid) {
    if (!$this->load($uid)) {
      return;
    }
    return $this->tupasSession->get($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function start($uid, $transaction_id) {
    $config = $this->configFactory->get('tupas_session.settings');

    if (!$account = $this->load($uid)) {
      return FALSE;
    }
    $expire = (int) $config->get('tupas_session_length') * 60 + REQUEST_TIME;
    $this->tupasSession->save($account, $transaction_id, $expire);

    // Grant user role.
    $account->addRole('tupas_authenticated_user')
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function destroy($uid) {
    $config = $this->configFactory->get('tupas_session.settings');

    if (!$account = $this->load($uid)) {
      return FALSE;
    }
    // Remove tupas authenticated role.
    // @todo Should we always remove role when destroying session or just when
    // tupas_session_length is enabled?
    if (!empty($config->get('tupas_session_length'))) {
      $account->removeRole('tupas_authenticated_user')
        ->save();
    }
    $this->tupasSession->delete($uid);
  }

}
