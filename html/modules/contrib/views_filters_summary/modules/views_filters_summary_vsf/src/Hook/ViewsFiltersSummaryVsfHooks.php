<?php

namespace Drupal\views_filters_summary_vsf\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_selective_filters\Plugin\views\filter\Selective;

/**
 * Hook implementations for views_filters_summary_vsf.
 */
class ViewsFiltersSummaryVsfHooks {

  /**
   * Implements hook_views_filters_summary_info_alter().
   */
  #[Hook('views_filters_summary_info_alter')]
  public function infoAlter(array &$info, FilterPluginBase $filter) {

    if (!($filter instanceof Selective) || !is_array($info['value'])) {
      return;
    }

    $values = [];

    foreach ($filter->value as $index => $index_value) {
      // Skip not selected options.
      if ($index != $index_value) {
        continue;
      }

      $value_options = $filter->getValueOptions();
      $value = $value_options[$index_value];

      $values[] = [
        'id' => $index,
        'raw' => (string) $index,
        'value' => $value,
      ];
    }

    $info['value'] = $values;
  }

}
