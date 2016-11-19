<?php

namespace Drupal\tupas_registration\Controller;

use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas_registration\UniqueUsernameInterface;
use Drupal\tupas_session\Controller\SessionController;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\tupas_session\TupasTransactionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The unique username generator.
   *
   * @var \Drupal\tupas_registration\UniqueUsernameInterface
   */
  protected $usernameGenerator;

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
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   * @param \Drupal\tupas_registration\UniqueUsernameInterface $username_generator
   *   The unique username generator.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, TupasSessionManagerInterface $session_manager, TupasTransactionManagerInterface $transaction_manager, ExternalAuthInterface $auth, AuthmapInterface $authmap, UniqueUsernameInterface $username_generator) {
    parent::__construct($event_dispatcher, $session_manager, $transaction_manager);

    $this->usernameGenerator = $username_generator;
    $this->authmap = $authmap;
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
      $container->get('externalauth.externalauth'),
      $container->get('externalauth.authmap'),
      $container->get('tupas_registration.unique_username')
    );
  }

  /**
   * Page callback for /user/tupas/register.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *    Formbuilder form object.
   */
  public function register(Request $request) {
    // Make sure user has active TUPAS session.
    if (!$session = $this->sessionManager->getSession()) {
      drupal_set_message($this->t('TUPAS session not found.'), 'error');
      // Return to tupas initialize page.
      return $this->redirect('tupas_session.front');
    }
    $bank = $this->entityTypeManager()
      ->getStorage('tupas_bank')
      ->load($session->getData('bank'));

    if (!$bank instanceof TupasBank) {
      drupal_set_message($this->t('Validation failed.'), 'error');

      return $this->redirect('<front>');
    }
    $user_found = FALSE;

    // Check if user has already connected their account.
    if ($this->auth->load($session->getUniqueId(), 'tupas_registration')) {
      if ($this->currentUser()->isAuthenticated()) {
        drupal_set_message($this->t('You have already connected your account with TUPAS service.'), 'warning');

        return $this->redirect('<front>');
      }
      $user_found = TRUE;
    }
    // Attempt to migrate legacy (Drupal 7) users.
    // @see https://www.drupal.org/node/2639222
    elseif ($customer_id = $request->query->get('B02K_CUSTID')) {
      $legacy_hash = $bank->legacyHash($customer_id);

      if ($account = $this->auth->load($legacy_hash, 'tupas_registration')) {
        // Migrate legacy user.
        $this->authmap->save($account, 'tupas_registration', $session->getUniqueId());
        $user_found = TRUE;
      }
    }
    // Legacy/normal user found. Log the user in.
    if ($user_found) {
      $this->sessionManager->login($this->auth);

      return $this->redirect('<front>');
    }

    // Show map account confirmation form if user is already logged in.
    if ($this->currentUser()->isAuthenticated()) {
      return $this->formBuilder()
        ->getForm('\Drupal\tupas_registration\Form\MapTupasConfirmForm');
    }
    // Show custom registration form if user is not allowed to register without
    // filling the registration form.
    if (!$this->config('tupas_registration.settings')->get('disable_form')) {
      $entity = $this->entityTypeManager()->getStorage('user')->create();

      // Call our custom registration form.
      return $this->entityFormBuilder()
        ->getForm($entity, 'tupas_registration');
    }
    if ($account = $this->sessionManager->loginRegister($this->auth)) {
      // Attempt to use customer name and fallback to random name.
      $name = $this->usernameGenerator->getName($session->getData('name'));
      // Save user details.
      $account->setUsername($name)
        ->setPassword(user_password(20));
      $account->save();
    }
    return $this->redirect('<front>');
  }

}
