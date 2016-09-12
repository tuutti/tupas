<?php

namespace Drupal\tupas\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TupasBankForm.
 *
 * @package Drupal\tupas\Form
 */
class TupasBankForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $tupas_bank = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $tupas_bank->label(),
      '#description' => $this->t("Label for the Tupas bank."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $tupas_bank->id(),
      '#machine_name' => [
        'exists' => '\Drupal\tupas\Entity\TupasBank::load',
      ],
      '#disabled' => !$tupas_bank->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $tupas_bank->getStatus(),
      '#title' => $this->t('Enabled'),
    ];

    $form['action_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action URL'),
      '#default_value' => $tupas_bank->getActionUrl(),
    ];

    $form['cert_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Certificate version (A01Y_VERS)'),
      '#default_value' => $tupas_bank->getCertVersion(),
    ];

    $form['id_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Id type (A01Y_IDTYPE)'),
      '#default_value' => $tupas_bank->getIdType(),
      '#description' => $this->t('This must be set to "02" when using TUPAS registration module.'),
    ];

    $form['rcv_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Receiver ID (A01Y_RCVID)'),
      '#default_value' => $tupas_bank->getRcvId(),
    ];

    $form['rcv_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Receiver key'),
      '#default_value' => $tupas_bank->getRcvKey(),
    ];

    $form['key_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key version (A01Y_KEYVERS)'),
      '#default_value' => $tupas_bank->getKeyVersion(),
    ];

    $form['encryption_alg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encryption algorithm (A01Y_ALG)'),
      '#default_value' => $tupas_bank->getEncryptionAlg(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tupas_bank = $this->entity;
    $status = $tupas_bank->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Tupas bank.', [
          '%label' => $tupas_bank->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Tupas bank.', [
          '%label' => $tupas_bank->label(),
        ]));
    }
    $form_state->setRedirectUrl($tupas_bank->urlInfo('collection'));
  }

}
