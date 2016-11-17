<?php

namespace Drupal\tupas_session_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DefaultController.
 *
 * @package Drupal\tupas_session_test\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * Index.
   *
   * @return array
   *   The render array.
   */
  public function index() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: index.'),
    ];
  }

}
