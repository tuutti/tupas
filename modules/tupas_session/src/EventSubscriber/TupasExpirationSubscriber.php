<?php

namespace Drupal\tupas_session\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tupas_session\TupasSessionManagerInterface;
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
    $session = $this->sessionManager->getSession($account->id());

    // No session found or has not expired.
    if (!isset($session->expire) || $session->expire > REQUEST_TIME) {
      return;
    }
    drupal_set_message($this->t('Your TUPAS authentication has expired'), 'warning');

    $this->sessionManager->destroy($account->id());
    // Redirect to expired page.
    $url = Url::fromRoute($this->config->get('expired_goto'));
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}
