<?php

namespace Drupal\Tests\tupas_session\Functional;

use Drupal\tupas\Entity\TupasBank;
use Drupal\user\Entity\Role;

/**
 * Functional tests for tupas_session.
 *
 * @group tupas
 */
class TupasSessionFunctionalTest extends TupasSessionFunctionalBase {

  /**
   * Test tupas forms.
   */
  public function testTupasForms() {
    $this->drupalGet('/user/tupas/login');
    // Make sure users cannot access form without permission.
    $this->assertSession()->statusCodeEquals(403);

    // Make sure page contains multiple tupas forms.
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);
    $this->drupalGet('/user/tupas/login');
    $this->assertSession()->responseContains('data-drupal-selector="tupas-form-2"');
  }

  /**
   * Test anonymous authentication.
   */
  public function testAnonymousTupasReturn() {
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);
    $this->drupalGet('/user/tupas/cancel');
    $this->assertSession()->pageTextContains('TUPAS authentication was canceled by user.');

    $this->drupalGet('/user/tupas/rejected');
    $this->assertSession()->pageTextContains('TUPAS authentication was rejected.');

    // Make sure we cant access page without bank argument (B02K_TIMESTMP).
    $this->drupalGet('/user/tupas/authenticated');
    $this->assertSession()->pageTextContains('Missing required bank id argument.');

    // Test invalid bank id.
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => ['B02K_TIMESTMP' => 666 . REQUEST_TIME],
    ]);
    $this->assertSession()->pageTextContains('Validation failed.');

    // Test valid bank, but missing transaction id.
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => ['B02K_TIMESTMP' => 410 . REQUEST_TIME],
    ]);
    $this->assertSession()->pageTextContains('Transaction not found or expired.');

    // Visit form page to generate transaction id.
    $this->drupalGet('/user/tupas/login');

    $bank = TupasBank::load('aktia');
    $transaction_id = $this->getTransactionId();
    // Test with invalid customer id.
    $query = $this->generateBankMac($bank, $transaction_id, [
      'B02K_CUSTID' => 1234,
    ]);
    $this->drupalGet('/user/tupas/authenticated', [
      'query' => $query,
    ]);
    $this->assertSession()->pageTextContains('TUPAS authentication failed.');

    // Test succesful authentication.
    $this->loginUsingTupas();
  }

  /**
   * Test authenticated return.
   */
  public function testAuthenticated() {
    // Grant bypass tupas session expiration to prevent
    // TupasSessionEventSubscriber from logging us out.
    $account = $this->createUser(['access tupas', 'bypass tupas session expiration']);
    $this->drupalLogin($account);

    // Repeat same tests for authenticated user.
    $this->testAnonymousTupasReturn();

    // Make sure tupas session does not get destroyed on logout.
    // At this point we should already have an active tupas session
    // from testAnonymousTupasReturn().
    $this->drupalLogout();
    $this->assertNotEmpty($this->loadTupasSession($account));

    // Enable session destroy on logout feature.
    $this->config('tupas_session.settings')
      ->set('destroy_session_on_logout', 1)
      ->save();

    $this->drupalLogin($account);
    // Make sure session gets destroyed on logout.
    $this->drupalLogout();
    $this->assertFalse($this->loadTupasSession($account));
  }

  /**
   * Test event subscribers.
   */
  public function testEventResponses() {
    // Allow anonymous users to use tupas auth.
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);
    // Make sure require_session is enabled.
    $this->config('tupas_session.settings')
      ->set('require_session', TRUE)
      ->save();

    $role = Role::load(Role::AUTHENTICATED_ID);
    // Give temporarily access to bypass tupas expiration so user does
    // not instantly log out and fails the test.
    $account = $this->drupalCreateUser(['access tupas']);
    $this->grantPermissions($role, ['bypass tupas session expiration']);
    $this->drupalLogin($account);
    $this->removePermissions($role, ['bypass tupas session expiration']);

    // Make sure user gets logged out.
    $this->drupalGet('/user/tupas/login');
    $this->assertSession()->pageTextContains('Current role does not allow users to log-in without an active TUPAS session.');
    // Test that user is logged out.
    $this->drupalGet('/user');
    $this->assertSession()->fieldExists('name');

    // Disable require_session.
    $this->config('tupas_session.settings')
      ->set('require_session', FALSE)
      ->save();

    // Log the current user out.
    $this->forceLogout();

    // Make sure account keeps logged in without bypass tupas session when
    // require_session is disabled.
    $account = $this->drupalCreateUser(['access tupas']);
    $this->drupalLogin($account);
    // Test twice to be sure.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('/user');
      $this->assertSession()->pageTextNotContains('Current role does not allow users to log-in without an active TUPAS session.');
    }
    // Make sure session access timestamp is automatically refreshed.
    $session_manager = $this->container->get('tupas_session.session_manager');
    $session_manager->start(random_int(123456, 2345678), '123456-789A', [
      'bank' => 'aktia',
      'name' => 'Test Name',
    ]);
    $access = $session_manager->getSession()->getAccess();
    sleep(2);
    $this->drupalGet('/user');
    $access2 = $session_manager->getSession()->getAccess();
    $this->assertTrue(!empty($access) && $access2 > $access);

    // Disable session autorefresh.
    $this->config('tupas_session.settings')
      ->set('tupas_session_renew', FALSE)
      ->save();

    sleep(2);
    // Make sure session access stamp does not increase when session
    // renew is disabled.
    $this->drupalGet('/user/tupas/login');
    $access3 = $session_manager->getSession()->getAccess();
    $this->assertTrue(!empty($access3) && $access3 == $access2);
  }

  /**
   * Make sure access check works.
   */
  public function testAccessCheck() {
    // Allow anonymous users to use tupas auth.
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);

    $this->drupalGet('/tupas_session_test');
    // Make sure user has no access without an active tupas session.
    $this->assertSession()->statusCodeEquals(403);

    // Make sure user has access after authenticating using tupas.
    $this->loginUsingTupas();

    $this->drupalGet('/tupas_session_test');
    $this->assertSession()->pageTextContains('Implement method: index.');

    // Test tupas logout.
    $this->tupasLogout();

    // Test session page after logout.
    $this->drupalGet('/tupas_session_test');
    $this->assertSession()->statusCodeEquals(403);
  }

}
