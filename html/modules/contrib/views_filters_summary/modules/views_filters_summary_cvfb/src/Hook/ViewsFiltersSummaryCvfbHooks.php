<?php

namespace Drupal\views_filters_summary_cvfb\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for views_filters_summary_cvfb.
 */
class ViewsFiltersSummaryCvfbHooks {

  /**
   * Constructs a new ViewsFiltersSummaryCvfbHooks object.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension) {
    if ($extension === 'views_filters_summary' && isset($libraries['views_filters_summary'])) {
      $module_path = $this->moduleExtensionList->getPath('views_filters_summary_cvfb');
      $libraries['views_filters_summary']['js']['/' . $module_path . '/js/views-filters-summary-cvfb.js'] = [];
    }
  }

}
