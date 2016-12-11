<?php

namespace Drupal\Tests\tupas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas\Entity\TupasBank;
use Tupas\Exception\TupasGenericException;

/**
 * TupasBank unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas\Entity\TupasBank
 */
class TupasBankTest extends UnitTestCase {

  /**
   * The mocked Tupas Bank entity.
   *
   * @var \Drupal\tupas\Entity\TupasBankInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $bank;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $values = [
      'encryption_alg' => '01',
      'id_type' => '02',
    ];
    $this->bank = new TupasBank($values, 'tupas_bank');
  }

  /**
   * Test hashResponseId() method.
   *
   * @covers ::hashResponseId
   * @covers ::getHashableTypes
   * @covers ::validIdType
   */
  public function testHashResponseId() {
    $invalid = '1234567';

    $this->setExpectedException(TupasGenericException::class);
    $this->bank->hashResponseId($invalid);

    $this->bank->set('id_type', 02);
    // Test invalid id (returns input as it was given).
    $response = $this->bank->hashResponseId($invalid);
    $this->assertEquals($invalid, $response);

    $this->bank->hashResponseId('123456-123A');
  }

}
