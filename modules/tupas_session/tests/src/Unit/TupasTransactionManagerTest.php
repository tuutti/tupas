<?php

namespace Drupal\Tests\tupas_session\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas_session\TupasTransactionManager;

/**
 * TupasSessionNanager unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas_session\TupasTransactionManager
 */
class TupasTransactionManagerTest extends UnitTestCase {

  /**
   * The mocked tupas session storage.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The mocked session manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $sessionManager;

  /**
   * The transaction manager.
   *
   * @var \Drupal\tupas_session\TupasTransactionManager
   */
  protected $transactionManager;

  /**
   * Temp store factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $storageFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = $this->getMockBuilder('\Drupal\user\PrivateTempStore')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storageFactory = $this->getMockBuilder('\Drupal\Core\TempStore\PrivateTempStoreFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storageFactory->expects($this->any())
      ->method('get')
      ->with('tupas_session')
      ->willReturn($this->storage);

    $this->sessionManager = $this->getMockBuilder('\Drupal\Core\Session\SessionManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->transactionManager = new TupasTransactionManager($this->sessionManager, $this->storageFactory);
  }

  /**
   * Test regenerate() method.
   *
   * @covers ::__construct
   * @covers ::regenerate
   * @covers ::get
   * @covers ::delete
   */
  public function testRegenerate() {

    $transaction_id = $this->transactionManager->regenerate();

    $this->storage->expects($this->at(0))
      ->method('get')
      ->will($this->returnValue($transaction_id));

    $this->storage->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue(FALSE));

    $this->storage->expects($this->at(0))
      ->method('delete')
      ->will($this->returnValue(TRUE));

    $this->assertTrue(mb_strlen($transaction_id) === 6);
    $this->assertTrue($transaction_id === $this->transactionManager->get());

    $this->transactionManager->delete();

    $this->assertNull($this->transactionManager->get());
  }

}
