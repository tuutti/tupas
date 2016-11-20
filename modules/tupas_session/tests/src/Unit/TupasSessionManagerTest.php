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

    $this->eventDispatcher->expects($this->any())
      ->method('dispatch')
      ->will($this->returnValue($dispatched_event));

    $this->tupasSessionManager = $this->getMockBuilder(TupasSessionManager::class)
      ->setConstructorArgs([
        $this->configFactory,
        $this->storage,
        $this->sessionManager,
        $this->eventDispatcher,
      ])
      ->setMethods(['startNativeSession'])
      ->getMock();
  }

  /**
   * Test getSession() method.
   *
   * @covers ::getSession
   */
  public function testGetSession() {
    $this->storage->expects($this->at(0))
      ->method('get')
      ->will($this->returnValue($this->session));

    $this->storage->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue(0));

    // Test correct session.
    $result = $this->tupasSessionManager->getSession();
    $this->assertEquals($this->session, $result);

    // Test session not found.
    $result = $this->tupasSessionManager->getSession();
    $this->assertFalse($result);
  }

  /**
   * Test start() method.
   *
   * @covers ::start
   * @covers ::getTime
   * @covers ::startNativeSession
   */
  public function testStart() {
    $this->storage->expects($this->once())
      ->method('save')
      ->will($this->returnValue(TRUE));

    $result = $this->tupasSessionManager->start($this->session->getTransactionId(), $this->session->getUniqueId(), $this->session->getData());
    $this->assertTrue($result);
  }

  /**
   * Test recreate() method.
   *
   * @covers ::recreate
   */
  public function testRecreate() {
    $this->storage->expects($this->once())
      ->method('get')
      ->will($this->returnValue($this->session));

    $session = $this->tupasSessionManager->recreate($this->session);
    $this->assertEquals($session, $this->session);
  }

  /**
   * Test renew() method.
   *
   * @covers ::renew
   * @covers ::getTime
   */
  public function testRenew() {
    $this->storage->expects($this->at(0))
      ->method('get')
      ->will($this->returnValue(0));

    $this->storage->expects($this->at(1))
      ->method('get')
      ->will($this->returnValue($this->session));

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

  /**
   * Tests getSetting() method.
   *
   * @covers ::getSetting
   * @dataProvider getSettingDataProvider
   */
  public function testGetSetting($key, $return) {
    $result = $this->tupasSessionManager->getSetting($key);
    $this->assertEquals($result, $return);
  }

  /**
   * Data provider for testGetSetting().
   */
  public function getSettingDataProvider() {
    return [
      ['tupas_session_length', 30],
      ['invalid_data', NULL],
    ];
  }

}
