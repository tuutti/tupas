<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManagerInterface;

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
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\tupas_session\TupasSession $tupas_session
   *   Tupas session.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactory $config_factory, TupasSession $tupas_session, EntityManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->tupasSession = $tupas_session;
    $this->entityManager = $entity_manager;
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
   * {@inheritdoc}
   */
  public function start($uid, $transaction_id) {
    $config = $this->configFactory->get('tupas_session.settings');

    if (!$account = $this->load($uid)) {
      return FALSE;
    }
    $expire = (int) $config->get('tupas_session_length') * 60 + REQUEST_TIME;
    $this->tupasSession->save($account, $transaction_id, $expire);
  }

  /**
   * {@inheritdoc}
   */
  public function destroy($uid) {
    $this->tupasSession->delete($uid);
  }

}
