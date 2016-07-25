<?php

namespace Drupal\tupas\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TupasSettingsForm.
 *
 * @package Drupal\tupas\Form
 */
class TupasSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tupas.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tupas_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tupas.settings');

    $form['idtype'] = [
      '#type' => 'textfield',
      '#title' => t('Identification type (A01Y_IDTYPE)'),
      '#description' => t('See the TUPAS authentication manual appendix 2 for reference'),
      '#default_value' => $config->get('idtype'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('tupas.settings')
      ->set('idtype', $form_state->getValue('idtype'))
      ->save();
  }

}
