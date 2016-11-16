<?php

namespace Drupal\Tests\tupas_session\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tupas_session\Event\SessionData;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Test basic tupas session functionality.
 *
 * @group tupas
 */
class TupasSessionTest extends KernelTestBase {

  /**
   * The session manager.
   *
   * @var \Drupal\tupas_session\TupasSessionManager
   */
  protected $sessionManager;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'tupas',
    'tupas_session',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('tupas_bank');
    $this->installConfig('tupas');
    $this->installSchema('tupas_session', 'tupas_session');
    $this->installConfig('tupas_session');

    // TupasSessionStorage::getOwner() requires this.
    $this->requestStack = new RequestStack();
    $request = Request::createFromGlobals();
    $this->requestStack->push($request);
    $this->requestStack->getCurrentRequest()->setSession(new Session());
    $this->container->set('request_stack', $this->requestStack);

    $this->sessionManager = $this->container->get('tupas_session.session_manager');
  }

  /**
   * Create new user entity.
   *
   * @param string $name
   *   Account name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   *   New account.
   */
  private function createUser($name) {
    $account = User::create([
      'name' => $name,
      'status' => 1,
    ]);
    $account->save();

    return $account;
  }

  /**
   * Test tupas session expire.
   */
  public function testExpirableSessionStart() {
    // Test session creation.
    $result = $this->sessionManager->start(random_int(10000, 100000), $this->randomString(), []);
    $this->assertTrue($result);

    // Make sure getSession() returns valid session.
    $session = $this->sessionManager->getSession();
    $this->assertTrue($session instanceof SessionData);

    // Make sure renew() extends session expiration.
    $_SERVER['REQUEST_TIME'] = REQUEST_TIME + 2;
    $this->sessionManager->renew();
    $new_session = $this->sessionManager->getSession();
    $this->assertTrue($new_session->getExpire() > $session->getExpire());
  }

  /**
   * Test non expirable session.
   */
  public function testNonExpirableSessionStart() {
    $this->config('tupas_session.settings')
      ->set('tupas_session_length', 0)
      ->save();

    $this->sessionManager->start(random_int(100, 1000), $this->randomString(), []);
    $session = $this->sessionManager->getSession();
    $this->assertTrue($session->getExpire() == 0);
  }

  /**
   * Test tupas session data.
   */
  public function testSessionData() {
    $this->sessionManager->start(random_int(100, 1000), $this->randomString(), [
      'random_test_data' => 1,
      'test' => ['this is array' => 1],
    ]);
    $session = $this->sessionManager->getSession();

    $this->assertEquals($session->getData('random_test_data'), 1);
  }

  /**
   * Test session destroy.
   */
  public function testSessionDestroy() {
    $this->sessionManager->start(random_int(10000, 100000), $this->randomString(), []);
    $this->sessionManager->destroy();
    $this->assertFalse($this->sessionManager->getSession());
  }

  /**
   * Test garbage collection.
   */
  public function testGarbageCollection() {
    // Make sure session length is 30 minutes.
    $this->config('tupas_session.settings')
      ->set('tupas_session_length', 30)
      ->save();
    $this->sessionManager->start(random_int(10000, 100000), $this->randomString(), []);
    // Test that gc() does not remove non expired sessions.
    $this->sessionManager->gc();
    $this->assertTrue($this->sessionManager->getSession() instanceof SessionData);

    // Test that expired sessions gets removed.
    $_SERVER['REQUEST_TIME'] = REQUEST_TIME + (31 * 60);
    $this->sessionManager->gc();
    $this->assertFalse($this->sessionManager->getSession());
  }

  /**
   * Test unique name.
   */
  public function testUniqueName() {
    $this->assertEquals('test', $this->sessionManager->uniqueName('test'));
    // Test random generated name.
    $this->assertTrue(mb_strlen($this->sessionManager->uniqueName()) == 10);

    $this->createUser('test');
    $this->assertEquals('test 1', $this->sessionManager->uniqueName('test'));

    // Make sure first + lastname gets capitalized.
    $this->assertEquals('Firstname Lastname', $this->sessionManager->uniqueName('firstname lastname'));

    $this->createUser('Firstname Lastname');
    $this->assertEquals('Firstname Lastname 1', $this->sessionManager->uniqueName('Firstname Lastname'));
  }

}
