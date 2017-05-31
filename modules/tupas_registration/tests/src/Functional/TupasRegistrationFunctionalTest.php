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
  public static $modules = [
    'tupas',
    'tupas_session',
    'tupas_registration',
    'externalauth',
  ];

  /**
   * Test registration process as unauthenticated user.
   */
  public function testAnonymousReturn() {
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);
    // Page should not be accessable without tupas session.
    $this->drupalGet('/user/tupas/register');
    $this->assertSession()->statusCodeEquals(403);

    $this->loginUsingTupas();
    // User should be redirected to /user/2 path after account has been
    // automatically created.
    $this->assertSession()->addressEquals('/user/2');

    // Logout and test login functionality.
    $this->drupalLogout();

    $this->loginUsingTupas();
    // User should be redirected to /user/2 path after succesful log-in.
    $this->assertSession()->addressEquals('/user/2');

    // Logout and test registration form.
    $this->drupalLogout();

    // Allow users to fill registration form.
    $this->config('tupas_registration.settings')
      ->set('disable_form', FALSE)
      ->save();

    // Test registration form.
    $this->loginUsingTupas([
      'B02K_CUSTID' => '654321-123A',
    ]);
    $this->assertSession()->addressEquals('/user/tupas/register');
    $this->assertSession()->fieldExists('name');

    $this->drupalGet('/user/tupas/register');
    $this->assertSession()->fieldExists('name');

    // Fill registration form.
    $this->getSession()->getPage()->fillField('name', 'Testaccount');
    $this->getSession()->getPage()->fillField('mail', 'test@example.com');
    $this->drupalPostForm(NULL, [], 'Create new account');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');
    $this->assertSession()->addressEquals('/user/3');

    // Make sure username mapping works.
    $this->config('tupas_registration.settings')
      ->set('use_tupas_name', TRUE)
      ->save();
    // @todo Is there a better way to flush config cache?
    drupal_flush_all_caches();

    $this->drupalLogout();

    $this->loginUsingTupas([
      // Test special characters.
      'B02K_CUSTNAME' => 'Anné Testääjå',
      'B02K_CUSTID' => '654321-211A',
    ]);
    // Make sure username is hidden.
    $this->assertSession()->fieldNotExists('name');
    $this->getSession()->getPage()->fillField('mail', 'test123@example.com');
    $this->drupalPostForm(NULL, [], 'Create new account');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');
    $this->assertSession()->addressEquals('/user/4');
    // Make sure user has same username as the one provided by
    // the tupas service.
    $title = $this->getSession()->getPage()->find('css', 'h1');
    $this->assertTrue(mb_strlen($title->getText()), 10);
    $this->assertEquals($title->getText(), 'Anné Testääjå');
  }

  /**
   * Test registration for authenticated user.
   */
  public function testAuthenticatedReturn() {
    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['access tupas']);

    // Test connecting tupas to existing user.
    $account = $this->drupalCreateUser(['access tupas', 'bypass tupas session expiration']);
    $this->drupalLogin($account);

    $bank = TupasBank::load('aktia');
    $this->loginUsingTupas();

    $this->assertSession()->addressEquals('/user/tupas/register');
    $this->drupalPostForm(NULL, [], 'Confirm');
    $this->assertSession()->pageTextContains('Account connected succesfully.');

    // Log current user out and test that user can log with previously
    // connected tupas.
    $this->drupalLogout();
    $this->loginUsingTupas();
    // Make sure user is succesfully logged in using tupas.
    $this->assertSession()->pageTextContains('TUPAS authentication succesful.');
    $this->assertSession()->addressEquals('/user/2');

    $this->drupalLogout();

    // Test legacy hash migration.
    $account = $this->drupalCreateUser(['access tupas', 'bypass tupas session expiration']);
    $authmap = $this->container->get('externalauth.authmap');
    $ssn = random_int(123456, 234567) . '-123A';
    $authmap->save($account, 'tupas_registration', $bank->legacyHash($ssn));

    $this->loginUsingTupas([
      'B02K_CUSTID' => $ssn,
    ]);
    // Make sure user is succesfully logged in using tupas (with legacy hash).
    $this->assertSession()->addressEquals('/user/3');

    $authname = $authmap->get($account->id(), 'tupas_registration');
    $this->assertEquals($authname, $bank->hashResponseId($ssn));
  }

}
