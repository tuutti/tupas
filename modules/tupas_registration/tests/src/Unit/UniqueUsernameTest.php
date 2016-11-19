<?php

namespace Drupal\Tests\tupas_registration\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas_registration\UniqueUsername;

/**
 * UniqueUsername unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas_registration\UniqueUsername
 */
class UniqueUsernameTest extends UnitTestCase {

  /**
   * The mocked entity storage.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The unique username generator.
   *
   * @var \Drupal\tupas_registration\UniqueUsername
   */
  protected $usernameGenerator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityTypeManagerInterface');

    $this->entityStorage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($this->entityStorage));

    $this->usernameGenerator = new UniqueUsername($this->entityManager);
  }

  /**
   * Test getName() method.
   *
   * @covers ::__construct
   * @covers ::userExists
   * @covers ::getName
   * @dataProvider usernameDataProvider
   */
  public function testUniqueName($given_name, $expected_name, $num_times = 0) {
    // Loop until we have reached $num_times and after that return FALSE.
    // This is used to simulate when the next available username
    // is taken $num_times in a row.
    for ($i = 0; $i < $num_times; $i++) {
      $this->entityStorage->expects($this->at($i))
        ->method('loadByProperties')
        ->will($this->returnValue(TRUE));
    }
    $this->entityStorage->expects($this->at($num_times))
      ->method('loadByProperties')
      ->will($this->returnValue(FALSE));

    $this->assertEquals($this->usernameGenerator->getName($given_name), $expected_name);
  }

  /**
   * Tests getName() method.
   *
   * @covers ::getName
   */
  public function testRandomName() {
    for ($i = 0; $i < 10; $i++) {
      $this->assertTrue(mb_strlen($this->usernameGenerator->getName()) === 10);
    }
  }

  /**
   * Data provider for testUniqueName().
   *
   * @return array
   *   Test cases.
   */
  public function usernameDataProvider() {
    return [
      ['test', 'test'],
      ['test', 'test 1', 1],
      ['test', 'test 6', 6],
      ['firstname lastname', 'Firstname Lastname'],
      ['firstname lastname', 'Firstname Lastname 1', 1],
      ['firstname lastname', 'Firstname Lastname 9', 9],
      ['FirstName LastName', 'Firstname Lastname'],
      ['FiRStnaME lAsTNaME', 'Firstname Lastname'],
    ];
  }

}
