<?php

namespace Drupal\Tests\tupas_session\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas_session\Event\SessionData;
use Drupal\tupas_session\TupasSessionManager;

/**
 * TupasSessionNanager unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas_session\TupasSessionManager
 */
class TupasSessionManagerTest extends UnitTestCase {

  /**
   * The stubbed config factory object.
   *
   * @var \PHPUnit_Framework_MockObject_MockBuilder
   */
  protected $configFactory;

  /**
   * The mocked tupas session storage.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * Tupas session manager.
   *
   * @var \Drupal\tupas_session\TupasSessionManager
   */
  protected $tupasSessionManager;

  /**
   * The mocked session manager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $sessionManager;

  /**
   * The mocked event dispatcher.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * Session object.
   *
   * @var \Drupal\tupas_session\Event\SessionData
   */
  protected $session;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    if (!defined('REQUEST_TIME')) {
      define('REQUEST_TIME', time());
    }
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub([
      'tupas_session.settings' => [
        'tupas_session_length' => 30,
      ],
    ]);
    $this->storage = $this->getMockBuilder('\Drupal\tupas_session\TupasSessionStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->sessionManager = $this->getMockBuilder('\Drupal\Core\Session\SessionManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->eventDispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $this->session = new SessionData(123456, $this->randomMachineName(), time(), []);

    $dispatched_event = $this->getMockBuilder('\Drupal\tupas_session\Event\SessionData')
      ->disableOriginalConstructor()
      ->getMock();

    $dispatched_event->expects($this->any())
      ->method('getExpire')
      ->will($this->returnValue($this->session->getExpire()));

    $dispatched_event->expects($this->any())
      ->method('getTransactionId')
      ->will($this->returnValue($this->session->getTransactionId()));

    $dispatched_event->expects($this->any())
      ->method('getUniqueId')
      ->will($this->returnValue($this->session->getUniqueId()));

    $dispatched_event->expects($this->any())
      ->method('getData')
      ->will($this->returnValue($this->session->getData()));

    $this->eventDispatcher->expects($this->any())
      ->method('dispatch')
      ->will($this->returnValue($dispatched_event));

    $this->tupasSessionManager = new TupasSessionManager($this->configFactory, $this->storage, $this->sessionManager, $this->eventDispatcher);
  }

  /**
   * Test getSession() method.
   *
   * @covers ::getSession
   */
  public function testGetSession() {
    $actual_data = [
      'expire' => 12345678,
      'data' => [
        'transaction_id' => 1234,
        'unique_id' => $this->randomMachineName(),
        'data' => [],
      ],
    ];
    $this->storage->expects($this->at(0))
      ->method('get')
      ->will($this->returnValue($actual_data));

    $this->storage->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue(0));

    $this->storage->expects($this->at(2))
      ->method('get')
      ->will($this->returnValue(['test' => 1]));

    $actual_object = new SessionData($actual_data['data']['transaction_id'], $actual_data['data']['unique_id'], $actual_data['expire'], $actual_data['data']['data']);
    // Test correct session.
    $result = $this->tupasSessionManager->getSession();
    $this->assertEquals($actual_object, $result);

    // Test session not found.
    $result = $this->tupasSessionManager->getSession();
    $this->assertFalse($result);

    // Test invalid data.
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->tupasSessionManager->getSession();
  }

  /**
   * Test start() method.
   *
   * @covers ::start
   */
  public function testStart() {
    $this->sessionManager->expects($this->any())
      ->method('isStarted')
      ->will($this->returnValue(1));

    $this->storage->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $result = $this->tupasSessionManager->start($this->session->getTransactionId(), $this->session->getUniqueId(), $this->session->getData());
    $this->assertTrue($result);
  }

  /**
   * Test migrate() method.
   *
   * @covers ::migrate
   */
  public function testMigrate() {
    $result = $this->tupasSessionManager->migrate($this->session);
    $this->assertNull($result);

    $result = $this->tupasSessionManager->migrate($this->session, function () {
      return TRUE;
    });
    $this->assertTrue($result);
  }

  /**
   * Test renew() method.
   *
   * @covers ::renew
   */
  public function testRenew() {
    $this->storage->expects($this->at(0))
      ->method('get')
      ->will($this->returnValue(0));

    $actual_data = [
      'expire' => $this->session->getExpire(),
      'data' => [
        'transaction_id' => $this->session->getExpire(),
        'unique_id' => $this->session->getUniqueId(),
        'data' => $this->session->getData(),
      ],
    ];
    $this->storage->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue($actual_data));

    $this->storage->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    // Test renew with invalid session.
    $result = $this->tupasSessionManager->renew();
    $this->assertFalse($result);

    // Test renew with working session.
    $result = $this->tupasSessionManager->renew();
    $this->assertTrue($result);
  }

}
