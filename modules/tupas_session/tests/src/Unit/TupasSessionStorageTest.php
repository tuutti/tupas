<?php

namespace Drupal\Tests\tupas_session\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tupas_session\Event\SessionData;
use Drupal\tupas_session\TupasSessionStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * TupasSessionStorage unit tests.
 *
 * @group tupas
 * @coversDefaultClass \Drupal\tupas_session\TupasSessionStorage
 */
class TupasSessionStorageTest extends UnitTestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * Mock statement.
   *
   * @var \Drupal\Core\Database\Statement|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $statement;

  /**
   * Mock select interface.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $select;

  /**
   * Mock delete class.
   *
   * @var \Drupal\Core\Database\Query\Delete
   */
  protected $delete;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The tupas session storage.
   *
   * @var \Drupal\tupas_session\TupasSessionStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Statement object.
    $this->statement = $this->getMockBuilder('Drupal\Core\Database\Driver\sqlite\Statement')
      ->disableOriginalConstructor()
      ->getMock();

    // Create a Mock Select object and set expectations.
    $this->select = $this->getMockBuilder('Drupal\Core\Database\Query\Select')
      ->disableOriginalConstructor()
      ->getMock();

    $this->select->expects($this->any())
      ->method('fields')
      ->will($this->returnSelf());
    $this->select->expects($this->any())
      ->method('condition')
      ->will($this->returnSelf());
    $this->select->expects($this->any())
      ->method('range')
      ->will($this->returnSelf());

    $this->select->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));

    $this->connection->expects($this->any())
      ->method('select')
      ->will($this->returnValue($this->select));

    // Create a Mock Delete object and set expectations.
    $this->delete = $this->getMockBuilder('Drupal\Core\Database\Query\Delete')
      ->disableOriginalConstructor()
      ->getMock();

    $this->delete->expects($this->any())
      ->method('condition')
      ->will($this->returnSelf());

    $this->delete->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));

    $this->requestStack = new RequestStack();
    $request = Request::createFromGlobals();
    $this->requestStack->push($request);

    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountProxyInterface');
    $this->currentUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->storage = new TupasSessionStorage($this->connection, $this->currentUser, $this->requestStack);
  }

  /**
   * Test save() method.
   *
   * @covers ::save
   * @covers ::__construct
   */
  public function testSave() {
    $expire = 1236789;
    $data = [];
    $session = new SessionData(random_int(123456, 234567), $this->randomMachineName(), $expire, $data);

    $merge = $this->getMockBuilder('Drupal\Core\Database\Query\Merge')
      ->disableOriginalConstructor()
      ->getMock();

    $merge->expects($this->any())
      ->method('keys')
      ->will($this->returnSelf());

    $merge->expects($this->any())
      ->method('fields')
      ->will($this->returnSelf());

    $merge->expects($this->any())
      ->method('execute')
      ->will($this->returnValue($this->statement));

    $this->connection->expects($this->once())
      ->method('merge')
      ->with($this->equalTo('tupas_session'))
      ->will($this->returnValue($merge));

    $this->storage->save($session);
  }

  /**
   * Test delete() method.
   *
   * @covers ::delete
   */
  public function testDelete() {
    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('tupas_session'))
      ->will($this->returnValue($this->delete));

    $this->storage->delete();
  }

  /**
   * Test get() method.
   *
   * @covers ::get
   */
  public function testGet() {
    $data = (object) [
      'owner' => $this->currentUser->id(),
      'access' => 12345678,
      'transaction_id' => random_int(100, 1000),
      'unique_id' => $this->randomMachineName(),
      'data' => serialize([]),
    ];
    $this->statement->expects($this->at(0))
      ->method('fetchObject')
      ->will($this->returnValue($data));

    $this->statement->expects($this->at(1))
      ->method('fetchObject')
      ->will($this->returnValue(FALSE));

    // Test valid session.
    $result = $this->storage->get();
    $this->assertTrue($result instanceof SessionData);

    // Test invalid session.
    $result = $this->storage->get();
    $this->assertFalse($result);
  }

  /**
   * Test deleteExpired() method.
   *
   * @covers ::deleteExpired
   */
  public function testDeleteExpired() {
    $this->connection->expects($this->once())
      ->method('delete')
      ->with($this->equalTo('tupas_session'))
      ->will($this->returnValue($this->delete));

    $this->storage->deleteExpired(123456);
  }

}
