<?php

namespace Drupal\tupas_registration\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * RegisterForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entitity query service.
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager service.
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, QueryFactory $entity_query, TupasSessionManagerInterface $session_manager, ExternalAuthInterface $external_auth) {
    parent::__construct($entity_manager, $language_manager, $entity_query);

    $this->sessionManager = $session_manager;
    $this->auth = $external_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity.query'),
      $container->get('tupas_session.session_manager'),
      $container->get('externalauth.externalauth')
    );
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
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $session = $this->sessionManager->getSession();

    // Shouldn't be possible to be empty, but lets make sure just in case.
    if (empty($session['unique_id'])) {
      drupal_set_message($this->t('Registration failed. Please try again later.'));

      return $form_state->setRedirect('<front>');
    }
    if ($account = $this->auth->loginRegister($session['unique_id'], 'tupas_registration')) {
      drupal_set_message($this->t('Registration successful. You are now logged in.'));

      // Update account details.
      $account->setUsername($form_state->getValue('name'))
        ->setEmail($form_state->getValue('mail'))
        ->setPassword(user_password(20));
      $account->save();
    }
    $form_state->setRedirect('<front>');
  }

}
