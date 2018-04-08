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

    /** @var \Drupal\tupas\Entity\TupasBank $tupas_bank */
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

    $form['bank_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Bank number.'),
      '#description' => $this->t('This is used to validate the bank when returning from TUPAS service.'),
      '#default_value' => $tupas_bank->getBankNumber(),
      '#required' => TRUE,
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
      '#description' => $this->t('Note: Changing this value will break authentication for existing users.<br />Available values when used with tupas_registration: @values', [
        '@values' => implode(', ', $tupas_bank::getHashableTypes()),
      ]),
    ];

    $form['rcv_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Receiver ID (A01Y_RCVID)'),
      '#default_value' => $tupas_bank->getReceiverId(),
    ];

    $form['rcv_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Receiver key'),
      '#default_value' => $tupas_bank->getReceiverKey(),
    ];

    $form['key_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key version (A01Y_KEYVERS)'),
      '#default_value' => $tupas_bank->getKeyVersion(),
    ];

    $form['encryption_alg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encryption algorithm (A01Y_ALG)'),
      '#default_value' => $tupas_bank->getAlgorithm(),
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
        $this->messenger()->addMessage($this->t('Created the %label Tupas bank.', [
          '%label' => $tupas_bank->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Tupas bank.', [
          '%label' => $tupas_bank->label(),
        ]));
    }
    $form_state->setRedirectUrl($tupas_bank->urlInfo('collection'));
  }

}
