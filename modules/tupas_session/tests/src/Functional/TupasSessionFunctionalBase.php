<?php

namespace Drupal\Tests\tupas_session\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\BrowserTestBase;
use Drupal\tupas\Entity\TupasBankInterface;
use Drupal\tupas\TupasService;
use Drupal\user\RoleInterface;

/**
 * Functional tests for tupas_session.
 */
abstract class TupasSessionFunctionalBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['tupas', 'tupas_session'];

  /**
   * Generates bank mac.
   *
   * @param \Drupal\tupas\Entity\TupasBankInterface $bank
   *   Tupas bank.
   * @param int $transaction_id
   *   Transaction id.
   * @param array $overrides
   *   Override return values.
   *
   * @return array
   *   Fake return values from bank.
   */
  protected function generateBankMac(TupasBankInterface $bank, $transaction_id, array $overrides = []) {
    $tupas = new TupasService($bank, [
      'transaction_id' => $transaction_id,
    ]);

    $macstring = [];
    $return_values = [
      'B02K_VERS' => $bank->getCertVersion(),
      'B02K_TIMESTMP' => REQUEST_TIME,
      'B02K_IDNBR' => random_int(1000, 10000),
      'B02K_STAMP' => date('YmdHis', REQUEST_TIME) . $transaction_id,
      'B02K_CUSTNAME' => $this->randomString(),
      'B02K_KEYVERS' => $bank->getKeyVersion(),
      'B02K_ALG' => $bank->getEncryptionAlg(),
      'B02K_CUSTID' => '123456-123A',
      'B02K_CUSTTYPE' => '01',
    ];
    $return_values = array_merge($return_values, $overrides);

    foreach ($return_values as $value) {
      $macstring[] = $value;
    }
    // Append rcv key to mac.
    $macstring[] = $bank->getRcvKey();
    // Calculate the MAC based on the encryption algorithm.
    $return_values['B02K_MAC'] = $tupas->checksum($macstring);

    return $return_values;
  }

  /**
   * Remove permissions from a user role.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The ID of a user role to alter.
   * @param array $permissions
   *   (optional) A list of permission names to remove.
   */
  protected function removePermissions(RoleInterface $role, array $permissions) {
    foreach ($permissions as $permission) {
      $role->revokePermission($permission);
    }
    $role->trustData()->save();
  }

  /**
   * Get transaction id.
   *
   * @return string
   *   Url parts.
   */
  protected function getTransactionId() {
    $fields = $this->xpath('//input[@name="A01Y_RETLINK"]');
    $field = reset($fields)->getValue();
    $url = UrlHelper::parse($field);
    return $url['query']['transaction_id'];
  }

  /**
   * Force logout current user.
   */
  protected function forceLogout() {
    // Manually log out.
    unset($this->loggedInUser->sessionId);
    $this->loggedInUser = FALSE;
    $this->container->get('current_user')->setAccount(new AnonymousUserSession());

    return $this;
  }

}