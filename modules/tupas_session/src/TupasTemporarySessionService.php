<?php

namespace Drupal\tupas_session;

use Drupal\Core\Config\ConfigFactory;

/**
 * Class TupasTemporarySessionService.
 *
 * @package Drupal\tupas_session
 */
class TupasTemporarySessionService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

}
