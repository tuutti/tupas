<?php

namespace Drupal\tupas_registration\EventSubscriber;

use Drupal\tupas_session\Event\RedirectAlterEvent;
use Drupal\tupas_session\Event\SessionEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TupasRegistrationRedirectSubscriber.
 *
 * @package Drupal\tupas_registration
 */
class TupasRegistrationRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[SessionEvents::REDIRECT_ALTER] = ['redirectAlter'];
    return $events;
  }

  /**
   * Redirect to registration form on succesful TUPAS authentication.
   *
   * @param \Drupal\tupas_session\Event\RedirectAlterEvent $event
   *   The redirect alter event.
   */
  public function redirectAlter(RedirectAlterEvent $event) {
    $event->setPath('tupas_registration.register');
  }

}
