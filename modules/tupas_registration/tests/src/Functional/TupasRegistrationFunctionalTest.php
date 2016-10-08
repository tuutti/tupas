<?php

namespace Drupal\Tests\tupas_registration\Functional;

use Drupal\Tests\tupas_session\Functional\TupasSessionFunctionalBase;
use Drupal\tupas\Entity\TupasBank;
use Drupal\user\Entity\Role;

/**
 * Functional tests for tupas_registration.
 *
 * @group tupas
 */
class TupasRegistrationFunctionalTest extends TupasSessionFunctionalBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['tupas', 'tupas_session', 'tupas_registration'];

  /**
   * Test registration process as unauthenticated user.
   */
  public function testAnonymousReturn() {
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);
    // Page should not be accessable without tupas session.
    $this->drupalGet('/user/tupas/register');
    $this->assertSession()->pageTextContains('TUPAS session not found.');

    $bank = TupasBank::load('aktia');

    // Visit form page to generate transaction id.
    $this->drupalGet('/user/tupas/login');
    $transaction_id = $this->getTransactionId();
    // Test succesful authentication.
    $query = $this->generateBankMac($bank, $transaction_id);
    $query['bank_id'] = $bank->id();
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    $this->assertSession()->pageTextContains('TUPAS authentication succesful.');
    // User should be redirected to /user/2 path after account has been automatically created.
    $this->assertSession()->addressEquals('/user/2');

    // Logout and test login functionality.
    $this->drupalLogout();

    $this->drupalGet('/user/tupas/login');
    $transaction_id = $this->getTransactionId();
    $query = $this->generateBankMac($bank, $transaction_id);
    $query['bank_id'] = $bank->id();
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    $this->assertSession()->pageTextContains('TUPAS authentication succesful.');
    // User should be redirected to /user/2 path after succesful log-in.
    $this->assertSession()->addressEquals('/user/2');

    // Logout and test registration form.
    $this->drupalLogout();

    // Allow users to fill registration form.
    $this->config('tupas_registration.settings')
      ->set('disable_form', FALSE)
      ->save();

    // Test registration form.
    $this->drupalGet('/user/tupas/login');
    $transaction_id = $this->getTransactionId();
    $query = $this->generateBankMac($bank, $transaction_id, [
      'B02K_CUSTID' => '654321-123A',
    ]);
    $query['bank_id'] = $bank->id();
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    $this->assertSession()->addressEquals('/user/tupas/register');
    $this->assertSession()->fieldExists('name');

    // Fill registration form.
    $this->getSession()->getPage()->fillField('name', 'Testaccount');
    $this->getSession()->getPage()->fillField('mail', 'test@example.com');
    $this->drupalPostForm(NULL, [], 'Create new account');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');
  }

  /**
   * Test registration for authenticated user.
   */
  public function testAuthenticatedReturn() {
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);

    $account = $this->drupalCreateUser(['access tupas', 'bypass tupas session expiration']);
    $this->drupalLogin($account);

    $bank = TupasBank::load('aktia');
    // Test registration form.
    $this->drupalGet('/user/tupas/login');
    $transaction_id = $this->getTransactionId();
    $query = $this->generateBankMac($bank, $transaction_id);
    $query['bank_id'] = $bank->id();
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    $this->assertSession()->addressEquals('/user/tupas/register');
    $this->drupalPostForm(NULL, [], 'Confirm');
    $this->assertSession()->pageTextContains('Account connected succesfully.');

    // Log current user out and test that user can log with tupas.
    $this->drupalLogout();

    // Test registration form.
    $this->drupalGet('/user/tupas/login');
    $transaction_id = $this->getTransactionId();
    $query = $this->generateBankMac($bank, $transaction_id);
    $query['bank_id'] = $bank->id();
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    // Make sure user is succesfully logged in using tupas.
    $this->assertSession()->pageTextContains('TUPAS authentication succesful.');
    $this->assertSession()->addressEquals('/user/2');
  }

}
