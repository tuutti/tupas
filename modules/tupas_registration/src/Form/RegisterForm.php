<?php

namespace Drupal\tupas_registration\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager service.
   * @param \Drupal\externalauth\ExternalAuthInterface $auth
   *   The external auth service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, TupasSessionManagerInterface $session_manager, ExternalAuthInterface $auth, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $language_manager, $entity_type_bundle_info, $time);

    $this->sessionManager = $session_manager;
    $this->auth = $auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('tupas_session.session_manager'),
      $container->get('externalauth.externalauth'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
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

      drupal_set_message($this->t('Registration successful. You are now logged in.'));
    }
    else {
      // Delete account if registration failed.
      $entity->delete();

      drupal_set_message($this->t('Registration failed due to an unknown reason.'), 'error');
    }
    $form_state->setRedirect('<front>');
  }

}
