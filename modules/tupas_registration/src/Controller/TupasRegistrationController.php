<?php

namespace Drupal\tupas_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tupas\TupasService;

/**
 * Class TupasRegistrationController.
 *
 * @package Drupal\tupas_registration\Controller
 */
class TupasRegistrationController extends ControllerBase {

  /**
   * Drupal\tupas\TupasService definition.
   *
   * @var \Drupal\tupas\TupasService
   */
  protected $tupas;

  /**
   * {@inheritdoc}
   */
  public function __construct(TupasService $tupas) {
    $this->tupas = $tupas;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tupas')
    );
  }

  /**
   * Page callback displaying the bank buttons.
   *
   * @return array
   *   Render array containing the bank buttons markup.
   */
  public function register() {
    $banks = $this->entityManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

    $content['tupas_bank_items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tupas-bank-items']],
    ];
    foreach ($banks as $bank) {
      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasForm', $bank);
    }
    return $content;
  }

}
