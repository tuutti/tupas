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
    $form['generate_random_username'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always generate random username'),
      '#description' => $this->t('This is useful if you wish to store no identifiable information about the user.'),
      '#default_value' => $config->get('generate_random_username'),
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
      ->set('generate_random_username', $form_state->getValue('generate_random_username'))
      ->set('disable_form', $form_state->getValue('disable_form'))
      ->save();
  }

}
