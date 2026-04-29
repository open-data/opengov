<?php

namespace Drupal\views_filters_summary_a11y\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_filters_summary_a11y.
 */
class ViewsFiltersSummaryA11yHooks {

  /**
   * Constructs a new ViewsFiltersSummaryA11yHooks object.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
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
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension) {
    if ($extension === 'views_filters_summary' && isset($libraries['views_filters_summary'])) {
      $module_path = $this->moduleExtensionList->getPath('views_filters_summary_a11y');
      $libraries['views_filters_summary']['css']['theme']['/' . $module_path . '/css/views-filters-summary-a11y.css'] = [];
    }
  }

  /**
   * Implements hook_views_filters_summary_item_alter().
   */
  #[Hook('views_filters_summary_item_alter')]
  public function itemAlter(&$item) {
    $value = $item['value'];
    $aria_label = $item['link']['#attributes']['aria-label'];
    $item['link']['#title'] = [
      '#markup' =>
      '<span aria-hidden="true" class="remove-label">' . $value . '</span>' .
      '<span class="visually-hidden">' . $aria_label . '</span>' .
      '<span aria-hidden="true" class="remove-button">X</span>',
    ];
    $item['link']['#options'] = [
      'html' => TRUE,
    ];
  }

}
