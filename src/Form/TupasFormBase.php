<?php

namespace Drupal\tupas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tupas\Entity\TupasBankInterface;

/**
 * Class TupasFormBase.
 *
 * @package Drupal\tupas\Form
 */
class TupasFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tupas_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bank = []) {
    // This is supposed to be rendered manually.
    if (!$bank instanceof TupasBankInterface) {
      return $form;
    }
    $form['#action'] = $bank->getActionUrl();

    // Message type (defaults to '701' on all banks).
    $form['A01Y_ACTION_ID'] = [
      '#type' => 'hidden',
      '#value' => $bank::A01Y_ACTION_ID,
    ];

    // Version (depends on the bank).
    $form['A01Y_VERS'] = [
      '#type' => 'hidden',
      '#value' => $bank->getCertVersion(),
    ];

    // Service provider.
    $form['A01Y_RCVID'] = [
      '#type' => 'hidden',
      '#value' => $bank->getRcvId(),
    ];

    // Language code (by ISO 639 definition: FI = Finnish, SV = Swedish, EN = English).
    $form['A01Y_LANGCODE'] = [
      '#type' => 'hidden',
      '#value' => $bank->getLanguage(),
    ];

    // Personalization of the request.
    $form['A01Y_STAMP'] = [
      '#type' => 'hidden',
      '#value' => date('YmdHis', REQUEST_TIME) . $bank->getTransactionId(),
    ];

    // Type of the personalization data (see the TUPAS documentation appendix 2).
    $form['A01Y_IDTYPE'] = [
      '#type' => 'hidden',
      '#value' => $bank->getIdType(),
    ];

    // Return link on success.
    $form['A01Y_RETLINK'] = [
      '#type' => 'hidden',
      '#value' => $bank->getReturnUrl(),
    ];

    // Return link on cancel.
    $form['A01Y_CANLINK'] = [
      '#type' => 'hidden',
      '#value' => $bank->getCancelUrl(),
    ];

    // Return link on failure.
    $form['A01Y_REJLINK'] = [
      '#type' => 'hidden',
      '#value' => $bank->getRejectedUrl(),
    ];

    // MAC key version.
    $form['A01Y_KEYVERS'] = [
      '#type' => 'hidden',
      '#value' => $bank->getKeyVersion(),
    ];
    // Algorithm used to calculate the MAC (01 = MD5, 02 = SHA-1).
    $form['A01Y_ALG'] = [
      '#type' => 'hidden',
      '#value' => $bank->getEncryptionAlg(),
    ];

    $parts = [];
    foreach ($form as $key => $element) {
      if (substr($key, 0, 4) !== 'A01Y') {
        continue;
      }
      $parts[] = $element['#value'];
    }
    // Append bank's RCV key.
    $parts[] = $bank->getRcvKey();

    $form['A01Y_MAC'] = [
      '#type' => 'hidden',
      '#value' => $bank->checksum($parts),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $bank->label(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
