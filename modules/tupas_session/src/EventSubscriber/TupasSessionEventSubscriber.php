<?php

namespace Drupal\tupas_session\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class TupasExpirationSubscriber.
 *
 * @package Drupal\tupas_session
 */
class TupasExpirationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Tupas session manager service.
   *
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Constructor.
   *
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currenct user service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(TupasSessionManagerInterface $session_manager, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->config = $config_factory->get('tupas_session.settings');
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events['kernel.request'] = ['checkExpiration'];

    return $events;
  }

  /**
   * Attempt to change roles for given account.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Account to set roles to.
   * @param string $action
   *   Action to do.
   */
  protected function setRoles(AccountProxyInterface $account, $action = 'set') {
    if (!$account->isAuthenticated()) {
      return;
    }
    $active_user = User::load($account->id());

    if ($action === 'set') {
      $active_user->addRole('tupas_authenticated_user');
    }
    else {
      $active_user->removeRole('tupas_authenticated_user');
    }
    $active_user->save();
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @param GetResponseEvent $event
   *   Event to dispatch.
   */
  public function checkExpiration(GetResponseEvent $event) {
    $account = $this->currentUser->getAccount();

    if (empty($this->config->get('tupas_session_length')) || !$account->isAuthenticated()) {
      return;
    }
    $session = $this->sessionManager->getSession();

    // No session found.
    if (!isset($session->expire)) {
      return;
    }
    // Attempt to add role for current user.
    $this->setRoles($account, 'set');

    if ($session->expire > REQUEST_TIME) {
      return;
    }
    drupal_set_message($this->t('Your TUPAS authentication has expired'), 'warning');
    // Attempt to remove tupas_authenticated role.
    $this->setRoles($account, 'remove');

    $this->sessionManager->destroy();
    // Redirect to expired page.
    $url = Url::fromRoute($this->config->get('expired_goto'));
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}
