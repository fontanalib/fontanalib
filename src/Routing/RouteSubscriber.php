<?php

namespace Drupal\fontanalib\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.grants')) {
      $route->setRequirement('_custom_access', '\Drupal\fontanalib\AccessChecks\NodeGrantAccessCheck::access');
    }

  }

}