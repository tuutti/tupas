<?php

namespace Drupal\tupas_registration\Controller;

use Drupal\externalauth\AuthmapInterface;
use Drupal\tupas_session\Controller\SessionController;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TupasRegistrationController.
 *
 * @package Drupal\tupas_registration\Controller
 */
class RegistrationController extends SessionController {

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * RegistrationController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager service.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap service.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, TupasSessionManagerInterface $session_manager, AuthmapInterface $authmap) {
    parent::__construct($event_dispatcher, $session_manager);

    $this->authmap = $authmap;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('tupas_session.session_manager'),
      $container->get('externalauth.authmap')
    );
  }

  /**
   * Page callback for /user/tupas/register.
   *
   * @return array
   *   Formbuilder form object.
   */
  public function register() {
    if ($this->authmap->get($this->currentUser()->id(), 'tupas_registration')) {
      drupal_set_message($this->t('Your account is already connected'));

      return $this->redirect('<front>');
    }
    // Make sure user has active TUPAS session.
    if (!$this->sessionManager->getSession($this->currentUser()->id())) {
      drupal_set_message($this->t('TUPAS session not found.'), 'error');
      // Return to tupas initialize page.
      return $this->redirect('tupas_session.front');
    }
    // Show map account confirmation form if user is already logged in.
    if ($this->currentUser()->isAuthenticated()) {
      return $this->formBuilder()
        ->getForm('\Drupal\tupas_registration\Form\MapTupasConfirmForm');
    }
    return $this->formBuilder()
      ->getForm('\Drupal\tupas_registration\Form\RegisterForm');
  }

}
