<?php

namespace Drupal\views_filters_summary\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Hook implementations for views_filters_summary.
 */
class ViewsFiltersSummaryHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'views_filters_summary' => [
        'variables' => [
          'summary' => NULL,
          'options' => [],
          'exposed_form_id' => NULL,
        ],
      ],
      'views_filters_summary_items' => [
        'variables' => [
          'summary' => NULL,
          'options' => [],
        ],
      ],
      'views_filters_summary_item' => [
        'variables' => [
          'item' => NULL,
          'options' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data['views']['views_filters_summary'] = [
      'title' => $this->t('Views exposed filters summary'),
      'help' => $this->t('Shows result summary with filters.'),
      'area' => [
        'id' => 'views_filters_summary',
      ],
    ];

    return $data;
  }

  /**
   * Implements hook_views_filters_summary_filter_value_alter().
   */
  #[Hook('views_filters_summary_filter_value_alter')]
  public function filterValueAlter(
    mixed &$value,
    FilterPluginBase $filter,
  ) {
    // For some reason, the User Permissions plugin does not properly
    // populate the filter value.
    if ($filter->getPluginId() === 'user_permissions') {
      $inputs = $filter->view->getExposedInput();
      $value = $inputs[$filter->options['id']];
    }
  }

  /**
   * Implements hook_views_filters_summary_plugin_alias().
   */
  #[Hook('views_filters_summary_plugin_alias')]
  public function pluginAlias($filter) {
    switch ($filter->getPluginId()) {
      case 'user_permissions':
      case 'user_roles':
        return 'list_field';
    }
  }

}
