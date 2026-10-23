<?php

namespace Drupal\webform_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for webform UI.
 */
class WebformUiRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('entity.webform.edit_form')) {
      // Stop requiring the "edit webform source" permission, YAML source
      // editing is now on a separate route.
      $requirements = $route->getRequirements();
      unset($requirements['_permission']);
      $route->setRequirements($requirements);
    }
  }

}
