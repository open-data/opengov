<?php

namespace Drupal\views_filters_summary_bef\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Hook implementations for views_filters_summary_bef.
 */
class ViewsFiltersSummaryBefHooks {

  /**
   * Implements hook_views_filters_summary_filter_value_label_alter().
   */
  #[Hook('views_filters_summary_filter_value_label_alter')]
  public function filterValueLabelAlter(
    string &$label,
    string &$value,
    FilterPluginBase $filter,
  ) {
    // If the filter is a boolean filter and the filter is exposed
    // as a BEF single checkbox, then change the label to the
    // exposed label.
    if ($filter->getPluginId() === 'boolean') {
      $exposed_form = $filter->view->display_handler->getOption('exposed_form');
      $bef_options = $exposed_form['options']['bef']['filter'] ?? [];
      if (isset($bef_options[$filter->field]['plugin_id'])
        && $bef_options[$filter->field]['plugin_id'] === 'bef_single') {
        $label = $value ? $filter->options['expose']['label'] : NULL;
      }
    }
  }

}
