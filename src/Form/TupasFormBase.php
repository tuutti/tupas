<?php

namespace Drupal\tupas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tupas\TupasServiceInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state, $tupas = []) {
    // This is supposed to be rendered manually.
    if (!$tupas instanceof TupasServiceInterface) {
      return $form;
    }
    // @todo Figure out better caching mechanics.
    // Disable caching for this form to make sure every user gets unique
    // transaction id.
    $form['#cache'] = [
      'max-age' => 0,
    ];
    $form['#action'] = $tupas->getBank()->getActionUrl();

    // Message type (defaults to '701' on all banks).
    $form['A01Y_ACTION_ID'] = [
      '#type' => 'hidden',
      '#value' => $tupas::A01Y_ACTION_ID,
    ];

    // Version (depends on the bank).
    $form['A01Y_VERS'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getBank()->getCertVersion(),
    ];

    // Service provider.
    $form['A01Y_RCVID'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getBank()->getRcvId(),
    ];

    // Language code (by ISO 639 definition: FI = Finnish, SV = Swedish, EN = English).
    $form['A01Y_LANGCODE'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getLanguage(),
    ];

    // Personalization of the request.
    $form['A01Y_STAMP'] = [
      '#type' => 'hidden',
      '#value' => date('YmdHis', REQUEST_TIME) . $tupas->getTransactionId(),
    ];

    // Type of the personalization data (see the TUPAS documentation appendix 2).
    $form['A01Y_IDTYPE'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getBank()->getIdType(),
    ];

    // Return link on success.
    $form['A01Y_RETLINK'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getReturnUrl(),
    ];

    // Return link on cancel.
    $form['A01Y_CANLINK'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getCancelUrl(),
    ];

    // Return link on failure.
    $form['A01Y_REJLINK'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getRejectedUrl(),
    ];

    // MAC key version.
    $form['A01Y_KEYVERS'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getBank()->getKeyVersion(),
    ];
    // Algorithm used to calculate the MAC (01 = MD5, 02 = SHA-1).
    $form['A01Y_ALG'] = [
      '#type' => 'hidden',
      '#value' => $tupas->getBank()->getEncryptionAlg(),
    ];

    $parts = [];
    foreach ($form as $key => $element) {
      if (substr($key, 0, 4) !== 'A01Y') {
        continue;
      }
      $parts[] = $element['#value'];
    }
    // Append bank's RCV key.
    $parts[] = $tupas->getBank()->getRcvKey();

    $form['A01Y_MAC'] = [
      '#type' => 'hidden',
      '#value' => $tupas->checksum($parts),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $tupas->getBank()->label(),
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
