<?php

namespace Drupal\Tests\tupas_registration\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Test basic tupas session functionality.
 *
 * @group tupas
 */
class TupasRegistrationTest extends KernelTestBase {

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
   * The unique username generator.
   *
   * @var \Drupal\tupas_registration\UniqueUsername
   */
  protected $usernameGenerator;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'tupas',
    'tupas_session',
    'tupas_registration',
    'externalauth',
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
    $this->installSchema('externalauth', 'authmap');
    $this->installConfig('tupas_session');

    // TupasSessionStorage::getOwner() requires this.
    $this->requestStack = new RequestStack();
    $request = Request::createFromGlobals();
    $this->requestStack->push($request);
    $this->requestStack->getCurrentRequest()->setSession(new Session());
    $this->container->set('request_stack', $this->requestStack);

    $this->sessionManager = $this->container->get('tupas_session.session_manager');
    $this->usernameGenerator = $this->container->get('tupas_registration.unique_username');
  }

  /**
   * Create new user entity.
   *
   * @param string $name
   *   Account name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   New account.
   */
  protected function createUser($name) {
    $account = User::create([
      'name' => $name,
      'status' => 1,
    ]);
    $account->save();

    return $account;
  }

  /**
   * Test tupas session migration.
   */
  public function testLoginRegister() {
    $this->sessionManager->start(random_int(10000, 100000), $this->randomString(), []);

    $auth = $this->container->get('externalauth.externalauth');

    // Make sure sessions are identical after migrate.
    $session = $this->sessionManager->getSession();
    $account = $this->sessionManager->loginRegister($auth);
    $new_session = $this->sessionManager->getSession();
    $this->assertEquals($session, $new_session);
    $this->assertTrue($account->isAuthenticated());

    $this->sessionManager->destroy();
    $this->assertFalse($this->sessionManager->getSession());

    // Recreate tupas session with previous session data.
    $this->sessionManager->recreate($session);
    $session = $this->sessionManager->getSession();
    $account = $this->sessionManager->login($auth);
    $new_session = $this->sessionManager->getSession();

    $this->assertEquals($session, $new_session);
    $this->assertTrue($account->isAuthenticated());
  }

  /**
   * Test unique name.
   */
  public function testUniqueName() {
    $this->assertEquals('test', $this->usernameGenerator->getName('test'));
    // Test random generated name.
    $this->assertTrue(mb_strlen($this->usernameGenerator->getName()) == 10);

    $this->createUser('test');
    $this->assertEquals('test 1', $this->usernameGenerator->getName('test'));

    // Make sure incrementing works.
    foreach (range(1, 5) as $i) {
      $this->createUser('test ' . $i);
    }
    $this->assertEquals('test 6', $this->usernameGenerator->getName('test'));

    // Make sure first + lastname gets capitalized.
    $this->assertEquals('Firstname Lastname', $this->usernameGenerator->getName('firstname lastname'));

    $this->createUser('Firstname Lastname');
    $this->assertEquals('Firstname Lastname 1', $this->usernameGenerator->getName('Firstname Lastname'));
  }

}
