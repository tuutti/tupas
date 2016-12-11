<?php

namespace Drupal\tupas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tupas\Entity\TupasBankInterface;
use Tupas\Form\TupasFormInterface;

/**
 * Class TupasFormBase.
 *
 * @package Drupal\tupas\Form
 */
class TupasFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tupas_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tupas = [], $bank = []) {
    // This is supposed to be rendered manually.
    if (!$tupas instanceof TupasFormInterface || !$bank instanceof TupasBankInterface) {
      throw new \RuntimeException('Missing valid $tupas or $bank argument.');
    }
    $form['#action'] = $bank->getActionUrl();

    foreach ($tupas->build() as $key => $value) {
      $form[$key] = [
        '#type' => 'hidden',
        '#value' => $value,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $bank->label(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
