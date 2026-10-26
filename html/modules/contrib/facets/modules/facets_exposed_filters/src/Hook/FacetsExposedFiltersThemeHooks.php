<?php

namespace Drupal\facets_exposed_filters\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme hook integrations for exposed facet filters.
 */
final class FacetsExposedFiltersThemeHooks {

  /**
   * Implements hook_preprocess_bef_checkboxes().
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   */
  #[Hook('preprocess_bef_checkboxes')]
  public function preprocessBefCheckboxes(array &$variables): void {
    $this->filterBefChildren($variables);
  }

  /**
   * Implements hook_preprocess_bef_radios().
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   */
  #[Hook('preprocess_bef_radios')]
  public function preprocessBefRadios(array &$variables): void {
    $this->filterBefChildren($variables);
  }

  /**
   * Filters BEF checkbox/radio children to the selected facet result.
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   */
  private function filterBefChildren(array &$variables): void {
    $element = &$variables['element'];

    if (empty($element['#facets_show_only_one_result']) || empty($element['#facets_active_option_values']) || !is_array($element['#facets_active_option_values'])) {
      return;
    }

    $allowed_keys = [];
    if (!empty($element['#options']) && is_array($element['#options'])) {
      $allowed_keys = array_map('strval', array_keys($element['#options']));
    }

    if ($allowed_keys === []) {
      $allowed_keys = array_map('strval', $element['#facets_active_option_values']);
    }

    $allowed_lookup = array_fill_keys($allowed_keys, TRUE);

    foreach (Element::children($element) as $child_key) {
      if (!isset($allowed_lookup[(string) $child_key])) {
        unset($element[$child_key]);
      }
    }

    if (!empty($element['#options']) && is_array($element['#options'])) {
      $element['#options'] = array_filter(
        $element['#options'],
        static fn (mixed $label, string|int $key): bool => isset($allowed_lookup[(string) $key]),
        ARRAY_FILTER_USE_BOTH,
      );
    }

    $variables['children'] = array_values(array_filter(
      $variables['children'] ?? [],
      static fn (string|int $child_key): bool => isset($allowed_lookup[(string) $child_key]),
    ));

    if (!empty($element['#bef_nested'])) {
      _bef_preprocess_nested_elements($variables);
    }
  }

}
