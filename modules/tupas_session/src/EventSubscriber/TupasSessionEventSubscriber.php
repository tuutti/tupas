<?php

namespace Drupal\tupas_session\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tupas_session\TupasSessionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
   * Tupas session manager service.
   *
   * @var \Drupal\tupas_session\TupasSessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * @param \Drupal\tupas_session\TupasSessionManagerInterface $session_manager
   *   The tupas session manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currenct user service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(TupasSessionManagerInterface $session_manager, AccountProxyInterface $current_user, MessengerInterface $messenger) {
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Should go before other subscribers start to write their caches. Notably
    // before \Drupal\Core\EventSubscriber\KernelDestructionSubscriber to
    // prevent instantiation of destructed services.
    $events[KernelEvents::REQUEST][] = ['handleTupasSession', 300];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event to dispatch.
   */
  public function handleTupasSession(GetResponseEvent $event) {
    // User has access to bypass session expiration. Do nothing.
    if ($this->currentUser->hasPermission('bypass tupas session expiration')) {
      return;
    }
    if (!$session = $this->sessionManager->getSession()) {
      // Log the current user out if user has no active tupas session
      // and user has no permission to bypass this check.
      if ($this->sessionManager->getSetting('require_session') && !$this->currentUser->isAnonymous()) {
        $this->messenger->addWarning($this->t('Current role does not allow users to log-in without an active TUPAS session.'));

        user_logout();
      }
      return;
    }
    $expire = (int) $this->sessionManager->getSetting('tupas_session_length') * 60;

    // Session does not expire. Do nothing.
    if ($expire === 0) {
      return;
    }
    elseif ($expire + $session->getAccess() >= REQUEST_TIME) {
      // Automatically refresh expiration date.
      if ($this->sessionManager->getSetting('tupas_session_renew')) {
        $this->sessionManager->renew();
      }
      return;
    }
    // Session has expired. Destroy the session.
    $this->sessionManager->destroy();

    $this->messenger->addWarning($this->t('Your TUPAS authentication has expired.'));

    $url = Url::fromRoute('<front>');
    // Redirect to expired page.
    if ($path = $this->sessionManager->getSetting('expired_goto')) {
      $url = Url::fromRoute($path);
    }
    $event->setResponse(new RedirectResponse($url->toString()));
  }

}
