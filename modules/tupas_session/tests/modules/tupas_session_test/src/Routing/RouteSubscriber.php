<?php

namespace Drupal\tupas_session_test\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Remove csrf_token check for tests.
    if ($route = $collection->get('tupas_session.logout')) {
      $requirements = $route->getRequirements();

      if (isset($requirements['_csrf_token'])) {
        unset($requirements['_csrf_token']);
      }
      $route->setRequirements($requirements);
    }
  }

}

