<?php

namespace Drupal\tupas_temporary_session\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class settingsForm.
 *
 * @package Drupal\tupas_temporary_session\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tupas_temporary_session.settings',
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
    $config = $this->config('tupas_temporary_session.settings');

    $form['tupas_session_length'] = [
      '#type' => 'textfield',
      '#title' => t('TUPAS authentication session length in minutes'),
      '#description' => t('Set to 0 for no limit (expires only on logout)'),
      '#default_value' => $config->get('tupas_session_length'),
      '#required' => TRUE,
    ];

    $form['authenticated_goto'] = [
      '#type' => 'textfield',
      '#title' => t('Location of the return handler function'),
      '#description' => t('Use a Drupal menu path. Bank ID and (optional) transaction ID will be appended to the URL as parameters.'),
      '#default_value' => $config->get('authenticated_goto'),
    ];

    $form['canceled_goto'] = [
      '#type' => 'textfield',
      '#title' => t('Landing page after canceled TUPAS authentication'),
      '#description' => t('Use a Drupal menu path. Leave empty to use the front page.'),
      '#default_value' => $config->get('canceled_goto'),
    ];

    $form['rejected_goto'] = [
      '#type' => 'textfield',
      '#title' => t('Landing page after rejected TUPAS authentication'),
      '#description' => t('Use a Drupal menu path. Leave empty to use the front page.'),
      '#default_value' => $config->get('rejected_goto'),
    ];

    $form['expired_goto'] = [
      '#type' => 'textfield',
      '#title' => t('Landing page after expired TUPAS authentication'),
      '#description' => t('Use a Drupal menu path. Leave empty to use the front page.'),
      '#default_value' => $config->get('expired_goto'),
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

    $this->config('tupas_temporary_session.settings')
      ->set('tupas_session_length', $form_state->getValue('tupas_session_length'))
      ->set('authenticated_goto', $form_state->getValue('authenticated_goto'))
      ->set('canceled_goto', $form_state->getValue('canceled_goto'))
      ->set('rejected_goto', $form_state->getValue('rejected_goto'))
      ->set('expired_goto', $form_state->getValue('expired_goto'))
      ->save();
  }

}
