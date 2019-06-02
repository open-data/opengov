<?php

namespace Drupal\og_ext_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class UserRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the title of the user registration page.
    if ($route = $collection->get('user.register')) {
      $route->setDefaults([
        '_entity_form' => 'user.register',
        '_title' => 'Registration Page',
      ]);
    }

    $path = '/admin/help';
    foreach ($collection->all() as $route) {
      if (substr($route->getPath(), 0, 11) === $path) {
        $route->setRequirement(
          '_custom_access',
          '\Drupal\og_ext_user\AccessChecks\UserAccessChecks::access'
        );
      }
    }
  }

}
