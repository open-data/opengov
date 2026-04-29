<?php

namespace Drupal\views_filters_summary_eb\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for views_filters_summary_eb.
 */
class ViewsFiltersSummaryEbHooks {

  /**
   * Implements hook_views_filters_summary_exposed_form_id_alter().
   */
  #[Hook('views_filters_summary_exposed_form_id_alter')]
  public function exposedFormIdAlter(
    string &$exposed_form_id,
    ViewExecutable $view,
    DisplayPluginBase $display_handler,
  ) {
    if ($display_handler->getPluginId() === 'entity_browser') {
      // In an Entity Browser display, the View's exposed
      // filters are embedded within the browser's own form
      // (there is no separate
      // <form id="views-exposed-form-...">). The form ID
      // instead follows the pattern:
      // <form id="entity-browser-...">.
      $exposed_form_id = 'entity-browser-';
    }
  }

}
