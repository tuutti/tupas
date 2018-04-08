<?php

namespace Drupal\tupas_registration\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MapTupasConfirmForm.
 *
 * @package Drupal\tupas_registration\Form
 */
class MapTupasConfirmForm extends ConfirmFormBase {

  /**
   * External auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * The tupas session manager.
   *
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * MapTupasConfirmForm constructor.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth service.
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager.
   */
  public function __construct(ExternalAuthInterface $external_auth, TupasSessionManagerInterface $session_manager) {
    $this->externalAuth = $external_auth;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('externalauth.externalauth'),
      $container->get('tupas_session.session_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to connect TUPAS with the account %account?', [
      '%account' => $this->currentUser()->getDisplayName(),
    ]);
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'map_tupas_confirm_form';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('Account connected succesfully.'));
    $session = $this->sessionManager->getSession();
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load($this->currentUser()->id());

    $this->externalAuth->linkExistingAccount($session->getUniqueId(), 'tupas_registration', $account);
    $form_state->setRedirect('<front>');
  }

}
