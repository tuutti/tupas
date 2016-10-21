<?php

namespace Drupal\tupas_registration\EventSubscriber;

use Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TupasRegistrationExternalAuthSubscriber.
 *
 * @package Drupal\tupas_registration
 */
class TupasRegistrationExternalAuthSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[ExternalAuthEvents::AUTHMAP_ALTER][] = ['alterUsername'];

    return $events;
  }

  /**
   * Alter authmap username.
   *
   * @param \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent $event
   *   Event to dispatch.
   */
  public function alterUsername(ExternalAuthAuthmapAlterEvent $event) {
    // By default externalauth module generates username from auth_
    // service + authname. We use hashed SSN as authname so username
    // is gonna be longer than allowed 60 characters.
    $event->setUsername($event->getAuthname());
  }

}
