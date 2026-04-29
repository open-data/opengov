<?php

namespace Drupal\views_filters_summary_commerce\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_filters_summary_commerce.
 */
class ViewsFiltersSummaryCommerceHooks {

  /**
   * Implements hook_views_filters_summary_plugin_alias().
   */
  #[Hook('views_filters_summary_plugin_alias')]
  public function pluginAlias($filter) {
    if ($filter->getPluginId() === 'commerce_entity_bundle') {
      // The commerce_entity_bundle plugin behaves like the
      // bundle plugin.
      return 'bundle';
    }
  }

}
