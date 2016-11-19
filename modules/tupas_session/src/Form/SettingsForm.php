<?php

namespace Drupal\tupas_session\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class settingsForm.
 *
 * @package Drupal\tupas_session\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tupas_session.settings',
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
    $config = $this->config('tupas_session.settings');

    $form['tupas_session_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TUPAS authentication session length in minutes'),
      '#description' => $this->t('Set to 0 for no limit (expires only on logout)'),
      '#default_value' => $config->get('tupas_session_length'),
      '#required' => TRUE,
    ];

    $form['require_session'] = [
      '#type' => 'checkbox',
      '#title' => t('Require tupas session to stay logged-in'),
      '#default_value' => $config->get('require_session'),
    ];

    $form['destroy_session_on_logout'] = [
      '#type' => 'checkbox',
      '#title' => t('Destroy TUPAS session on logout'),
      '#default_value' => $config->get('destroy_session_on_logout'),
    ];

    $form['tupas_session_renew'] = [
      '#type' => 'checkbox',
      '#title' => t('Auto-renew TUPAS session'),
      '#default_value' => $config->get('tupas_session_renew'),
    ];

    $form['expired_goto'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Landing page after expired TUPAS authentication'),
      '#description' => $this->t('Use a Drupal menu path. Leave empty to disable.'),
      '#default_value' => $config->get('expired_goto'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      Url::fromRoute($form_state->getValue('expired_goto'))->toString();
    }
    catch (RouteNotFoundException $e) {
      $form_state->setErrorByName('expired_goto', $this->t('Landing page is not valid route.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('tupas_session.settings')
      ->set('expired_goto', $form_state->getValue('expired_goto'))
      ->set('tupas_session_length', $form_state->getValue('tupas_session_length'))
      ->set('require_session', $form_state->getValue('require_session'))
      ->set('destroy_session_on_logout', $form_state->getValue('destroy_session_on_logout'))
      ->set('tupas_session_renew', $form_state->getValue('tupas_session_renew'))
      ->save();
  }

}
