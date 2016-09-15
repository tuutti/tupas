<?php

namespace Drupal\tupas_session\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tupas_session\Event\SessionAlterEvent;
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
class TupasSessionEventSubscriber implements EventSubscriberInterface {

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
    $events['kernel.request'] = ['handleTupasSession'];

    return $events;
  }

  /**
   * Attempt to change roles for given account.
   *
   * @param object $account
   *   Account to set roles to.
   * @param string $action
   *   Action to do.
   */
  protected function setRoles($account, $action = 'add') {
    if (!method_exists($account, 'id') || $account->isAnonymous()) {
      return;
    }
    $active_user = &drupal_static(__FUNCTION__ . $account->id());

    if (!$active_user) {
      $active_user = User::load($account->id());
    }

    if ($action === 'add') {
      if ($active_user->hasRole('tupas_authenticated_user')) {
        return;
      }
      $active_user->addRole('tupas_authenticated_user');
    }
    else {
      if (!$active_user->hasRole('tupas_authenticated_user')) {
        return;
      }
      $active_user->removeRole('tupas_authenticated_user');
    }
    $active_user->save();
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @todo replace this with rules/actions?
   *
   * @param GetResponseEvent $event
   *   Event to dispatch.
   */
  public function handleTupasSession(GetResponseEvent $event) {
    return;
    $account = $this->currentUser->getAccount();

    if (empty($this->config->get('tupas_session_length'))) {
      return;
    }
    $session = $this->sessionManager->getSession();

    // No session found. Attempt to remove tupas authenticated role.
    if (!$session) {
      return $this->setRoles($account, 'remove');
    }
    // Attempt to add role for current user.
    $this->setRoles($account, 'add');

    if ($session->getExpire() > REQUEST_TIME) {
      // Automatically refresh expiration date.
      if ($this->config->get('tupas_session_renew')) {
        $this->sessionManager->renew();
      }
      return;
    }
    drupal_set_message($this->t('Your TUPAS authentication has expired'), 'warning');
    // Attempt to remove tupas_authenticated role.
    $this->setRoles($account, 'remove');

    $this->sessionManager->destroy();
    // Redirect to expired page.
    if ($this->config->get('expired_goto')) {
      $url = Url::fromRoute($this->config->get('expired_goto'));
      $event->setResponse(new RedirectResponse($url->toString()));
    }
  }

}
