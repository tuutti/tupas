<?php

namespace Drupal\tupas_temporary_session\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tupas\TupasService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SessionController.
 *
 * @package Drupal\tupas_temporary_session\Controller
 */
class SessionController extends ControllerBase {

  /**
   * Callback for /user/tupas/login path.
   *
   * @return array
   */
  public function front() {
    $banks = $this->entityManager()
      ->getStorage('tupas_bank')
      ->getEnabled();

    $content['tupas_bank_items'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tupas-bank-items']],
    ];
    $config = $this->config('tupas_temporary_session.settings');

    foreach ($banks as $bank) {
      $tupas = new TupasService($bank, [
        'language' => 'FI',
        'return_url' => $config->get('authenticated_goto'),
        'cancel_url' => $config->get('canceled_goto'),
        'rejected_url' => $config->get('rejected_goto'),
        'transaction_id' => rand(100000, 999999),
      ]);
      $content['tupas_bank_items'][] = $this->formBuilder()
        ->getForm('\Drupal\tupas\Form\TupasFormBase', $tupas);
    }
    return $content;
  }

  /**
   * Callback for /user/tupas/authenticated path.
   */
  public function returnTo(Request $request) {
    $bank = $this->entityManager()
      ->getStorage('tupas_bank')
      ->load($request->query->get('bank_id'));

    if (!$bank instanceof TupasBank) {
      throw new HttpException(502, 'Bank not found');
    }
    $hash_match = $this->tupas->isValid($request);

    if (!$hash_match) {
      throw new HttpException(502, 'Hash validation failed');
    }
    drupal_set_message()
    return $this->redirect('<front>');
  }

  /**
   * Callback for /user/tupas/cancel path.
   */
  public function cancel() {
  }

  /**
   * Callback for /user/tupas/rejected path.
   */
  public function rejected() {
  }

}
