<?php

namespace Drupal\tupas_registration\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\user\AccountForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RegisterForm.
 *
 * @package Drupal\tupas_registration\Form
 */
class RegisterForm extends AccountForm {

  /**
   * The tupas session manager service.
   *
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $auth;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\tupas_registration\Form\RegisterForm $instance */
    $instance = parent::create($container);

    // Use setters to inject custom services so we don't have to override the
    // parent constructor.
    return $instance->setExternalAuth($container->get('externalauth.externalauth'))
      ->setSessionManager($container->get('tupas_session.session_manager'));
  }

  /**
   * Sets the session manager.
   *
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $sessionManager
   *   The session manager.
   *
   * @return $this
   *   The self.
   */
  public function setSessionManager(TupasSessionManagerInterface $sessionManager) {
    $this->sessionManager = $sessionManager;
    return $this;
  }

  /**
   * Sets the external auth.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   The external auth.
   *
   * @return $this
   *   The self.
   */
  public function setExternalAuth(ExternalAuthInterface $externalAuth) {
    $this->auth = $externalAuth;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Disable name field if random username generation is enabled.
    if ($this->config('tupas_registration.settings')->get('use_tupas_name')) {
      $form['account']['name']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Create new account');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Should we respect account verification setting?
    // Remove unneeded values.
    $form_state->cleanValues();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $entity */
    $entity = $this->entity;
    // Populate the form state with the correct username if one does
    // not exist already.
    if (!$form_state->getValue('name')) {
      $form_state->setValue('name', $entity->getAccountName());
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $entity */
    $entity = $this->entity;
    // Force account to be active.
    $entity->set('status', TRUE);

    parent::save($form, $form_state);

    // Map tupas session to existing account after a succesful registration.
    if ($account = $this->sessionManager->linkExisting($this->auth, $entity)) {
      $this->sessionManager->login($this->auth);

      $this->messenger()->addMessage($this->t('Registration successful. You are now logged in.'));
    }
    else {
      // Delete account if registration failed.
      $entity->delete();

      $this->messenger()->addError($this->t('Registration failed due to an unknown reason.'));
    }
    $form_state->setRedirect('<front>');
  }

}
