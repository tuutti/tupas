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
