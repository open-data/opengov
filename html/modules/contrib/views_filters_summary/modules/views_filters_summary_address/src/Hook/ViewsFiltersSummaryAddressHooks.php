<?php

namespace Drupal\views_filters_summary_address\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_filters_summary_address.
 */
class ViewsFiltersSummaryAddressHooks {

  /**
   * Implements hook_views_filters_summary_plugin_alias().
   */
  #[Hook('views_filters_summary_plugin_alias')]
  public function pluginAlias($filter) {
    if ($filter->getPluginId() === 'administrative_area') {
      // The administrative_area plugin behaves like the
      // list_field plugin.
      return 'list_field';
    }
  }

  /**
   * Implements hook_views_filters_summary_valid_index().
   */
  #[Hook('views_filters_summary_valid_index')]
  public function validIndex($index, $filter) {
    if ($filter->getPluginId() === 'administrative_area') {
      // Array is similar to: ['AL' => 'AL', 'AK' => 'AK'].
      return is_string($index);
    }
  }

}
