<?php

namespace Drupal\tupas_registration\Controller;

use Drupal\tupas_session\Controller\SessionController;

/**
 * Class TupasRegistrationController.
 *
 * @package Drupal\tupas_registration\Controller
 */
class RegistrationController extends SessionController {

  /**
   * Page callback for /user/tupas/register.
   *
   * @return array
   */
  public function register() {
    if ($this->currentUser()->isAuthenticated()) {
      return $this->formBuilder()
        ->getForm('\Drupal\tupas_registration\Form\MapTupasConfirmForm');
    }
    return $this->formBuilder()
      ->getForm('\Drupal\tupas_registration\Form\RegisterForm');
  }

  /**
   * Override return url on bank buttons.
   *
   * @return string
   */
  public function getAuthenticatedGoto() {
    return 'tupas_registration.register';
  }

}
