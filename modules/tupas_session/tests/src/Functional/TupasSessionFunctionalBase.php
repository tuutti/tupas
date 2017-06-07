<?php

namespace Drupal\Tests\tupas_session\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\tupas\Entity\TupasBank;
use Drupal\tupas\Entity\TupasBankInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Tupas\TupasEncryptionTrait;

/**
 * Functional tests for tupas_session.
 */
abstract class TupasSessionFunctionalBase extends BrowserTestBase {

  use TupasEncryptionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['tupas', 'tupas_session', 'tupas_session_test'];

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
    $macstring = [];
    $return_values = [
      'B02K_VERS' => $bank->getCertVersion(),
      // All tests defaults to aktia. Use 410 as bank code.
      'B02K_TIMESTMP' => 410 . REQUEST_TIME,
      'B02K_IDNBR' => random_int(1000, 10000),
      'B02K_STAMP' => date('YmdHis', REQUEST_TIME) . $transaction_id,
      'B02K_CUSTNAME' => $this->randomString(),
      'B02K_KEYVERS' => $bank->getKeyVersion(),
      'B02K_ALG' => $bank->getAlgorithm(),
      'B02K_CUSTID' => '123456-123A',
      'B02K_CUSTTYPE' => '01',
    ];
    $return_values = array_merge($return_values, $overrides);
    // Customer name will be in Latin1 charset and urlencoded by the
    // tupas service.
    $return_values['B02K_CUSTNAME'] = urlencode(mb_convert_encoding($return_values['B02K_CUSTNAME'], 'ISO-8859-1'));

    foreach ($return_values as $value) {
      $macstring[] = $value;
    }
    // Append rcv key to mac.
    $macstring[] = $bank->getReceiverKey();
    // Calculate the MAC based on the encryption algorithm.
    $return_values['B02K_MAC'] = $this->checksum($macstring, $bank->getAlgorithm());

    return $return_values;
  }

  /**
   * Load tupas session for given user.
   *
   * @param \Drupal\user\UserInterface $owner
   *   The account to load session for.
   *
   * @return bool|array
   *    FALSE if no sessions found, array of sessions if session found.
   */
  protected function loadTupasSession(UserInterface $owner) {
    $db = $this->container->get('database');
    $session = $db->select('tupas_session', 's')
      ->fields('s')
      ->condition('owner', $owner->id())
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    return !empty($session) ? $session : FALSE;
  }

  /**
   * Login using tupas.
   *
   * @param array $overrides
   *   Allow request variables to be altered.
   */
  protected function loginUsingTupas(array $overrides = []) {
    // Visit form page to generate transaction id.
    $this->drupalGet('/user/tupas/login');

    $bank = TupasBank::load('aktia');
    $transaction_id = $this->getTransactionId();

    $query = $this->generateBankMac($bank, $transaction_id, $overrides);
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    $this->assertSession()->pageTextContains('TUPAS authentication succesful.');
  }

  /**
   * Logout tupas.
   */
  protected function tupasLogout() {
    $path = Url::fromRoute('tupas_session.logout');
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals(200);
    // Make sure user is redirected to front page.
    $front = Url::fromRoute('<front>');
    $this->assertSession()->addressEquals($front);
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
    $fields = $this->xpath('//input[@name="A01Y_STAMP"]');
    $field = reset($fields)->getValue();
    return substr($field, -6);
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
