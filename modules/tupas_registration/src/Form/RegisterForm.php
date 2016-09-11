<?php

namespace Drupal\tupas_registration\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RegisterForm as UserRegisterForm;

/**
 * Class RegisterForm.
 *
 * @package Drupal\tupas_registration\Form
 */
class RegisterForm extends UserRegisterForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    return $form;
  }

}
