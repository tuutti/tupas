<?php

namespace Drupal\tupas_session\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the tupas_session module.
 */
class SessionControllerTest extends WebTestBase {


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "tupas_session SessionController's controller functionality",
      'description' => 'Test Unit for module tupas_session and controller SessionController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests tupas_session functionality.
   */
  public function testSessionController() {
    // Check that the basic functions of module tupas_session.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
