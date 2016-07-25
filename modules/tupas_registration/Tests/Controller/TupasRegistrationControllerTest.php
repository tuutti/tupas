<?php

namespace Drupal\tupas_registration\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\tupas\TupasService;

/**
 * Provides automated tests for the tupas_registration module.
 */
class TupasRegistrationControllerTest extends WebTestBase {

  /**
   * Drupal\tupas\TupasService definition.
   *
   * @var \Drupal\tupas\TupasService
   */
  protected $tupas;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "tupas_registration TupasRegistrationController's controller functionality",
      'description' => 'Test Unit for module tupas_registration and controller TupasRegistrationController.',
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
   * Tests tupas_registration functionality.
   */
  public function testTupasRegistrationController() {
    // Check that the basic functions of module tupas_registration.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
