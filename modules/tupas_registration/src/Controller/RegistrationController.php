<?php

namespace Drupal\tupas_registration\Controller;

use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas_session\Controller\SessionController;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\tupas_session\TupasTransactionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TupasRegistrationController.
 *
 * @package Drupal\tupas_registration\Controller
 */
class RegistrationController extends SessionController {

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $auth;

  /**
   * RegistrationController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager service.
   * @param \Drupal\tupas_session\TupasTransactionManagerInterface $transaction_manager
   *   The transaction manager service.
   * @param \Drupal\externalauth\ExternalAuthInterface $auth
   *   The external auth service.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, TupasSessionManagerInterface $session_manager, TupasTransactionManagerInterface $transaction_manager, ExternalAuthInterface $auth) {
    parent::__construct($event_dispatcher, $session_manager, $transaction_manager);

    $this->auth = $auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('tupas_session.session_manager'),
      $container->get('tupas_session.transaction_manager'),
      $container->get('externalauth.externalauth')
    );
  }

  /**
   * Page callback for /user/tupas/register.
   *
   * @return array
   *   Formbuilder form object.
   */
  public function register() {
    // Make sure user has active TUPAS session.
    if (!$session = $this->sessionManager->getSession()) {
      drupal_set_message($this->t('TUPAS session not found.'), 'error');
      // Return to tupas initialize page.
      return $this->redirect('tupas_session.front');
    }
    $bank = $this->entityManager()
      ->getStorage('tupas_bank')
      ->load($session->getData('bank'));

    if (!$bank instanceof TupasBank) {
      drupal_set_message($this->t('Validation failed'), 'error');

      return $this->redirect('<front>');
    }
    // Check if user has already connected their account.
    if ($session->getUniqueId() && $this->auth->load($session->getUniqueId(), 'tupas_registration')) {
      if ($this->currentUser()->isAuthenticated()) {
        drupal_set_message($this->t('You have already connected your account with TUPAS service.'), 'warning');

        return $this->redirect('<front>');
      }
      // Create callback to call after session migrate is succesfull.
      $callback = function ($session) {
        return $this->auth->login($session->getUniqueId(), 'tupas_registration');
      };
      if ($this->sessionManager->migrate($session, $callback)) {
        return $this->redirect('<front>');
      }
    }

    // Show map account confirmation form if user is already logged in.
    if ($this->currentUser()->isAuthenticated()) {
      return $this->formBuilder()
        ->getForm('\Drupal\tupas_registration\Form\MapTupasConfirmForm');
    }
    // Show custom registration form if user is not allowed to register without
    // filling the registration form.
    if (!$this->config('tupas_registration.settings')->get('disable_form')) {
      $entity = $this->entityManager()->getStorage('user')->create();

      // Call our custom registration form.
      return $this->entityFormBuilder()
        ->getForm($entity, 'tupas_registration');
    }
    // Autoregister user without filling the registration form.
    $callback = function ($session) {
      return $this->auth->loginRegister($session->getUniqueId(), 'tupas_registration');
    };
    if ($account = $this->sessionManager->migrate($session, $callback)) {
      // Attempt to use customer name and fallback to random name.
      $name = $this->sessionManager->uniqueName($session->getData('name'));
      // Save user details.
      $account->setUsername($name)
        ->setPassword(user_password(20));
      $account->save();
    }
    return $this->redirect('<front>');
  }

}
