<?php

namespace Drupal\tupas_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\tupas_registration\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tupas_registration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tupas_registration.settings');

    $form['disable_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable registration form'),
      '#description' => $this->t('Force users to register without filling the registration form. Username and email will be auto-generated.'),
      '#default_value' => $config->get('disable_form'),
    ];
    $form['use_tupas_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always attempt to use username provided by TUPAS'),
      '#description' => $this->t('This will fallback to randomly generated name if no name is provided by the TUPAS service.'),
      '#default_value' => $config->get('use_tupas_name'),
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

    $this->config('tupas_registration.settings')
      ->set('use_tupas_name', $form_state->getValue('use_tupas_name'))
      ->set('disable_form', $form_state->getValue('disable_form'))
      ->save();
  }

}
