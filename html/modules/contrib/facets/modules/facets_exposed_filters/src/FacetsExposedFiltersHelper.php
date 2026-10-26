<?php

namespace Drupal\facets_exposed_filters;

use Drupal\facets\FacetInterface;

/**
 * Helper methods for exposed facet filter integrations.
 */
final class FacetsExposedFiltersHelper {

  /**
   * Stores processed facets for the duration of the request.
   *
   * @var array<string, array<string, array<string, \Drupal\facets\FacetInterface>>>
   */
  private static array $processedFacets = [];

  /**
   * Tracks views whose exposed facet form has been rebuilt after execution.
   *
   * @var array<string, array<string, bool>>
   */
  private static array $postExecutedViews = [];

  /**
   * Removes validation so dynamically limited options remain accepted.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param mixed $form
   *   The complete form structure.
   *
   * @return array<string, mixed>
   *   The processed form element.
   */
  public static function removeValidation(array $element, mixed $form): array {
    unset($element['#needs_validation']);
    return $element;
  }

  /**
   * Returns a nested summary item for a raw facet value.
   *
   * @param iterable $results
   *   The facet results to inspect.
   * @param mixed $rawValue
   *   The raw facet value.
   *
   * @return array<string, mixed>|false
   *   The summary item, or FALSE when not found.
   */
  public static function getNestedSummaryValue(iterable $results, mixed $rawValue): array|false {
    foreach ($results as $result) {
      if ($result->getRawValue() == $rawValue) {
        return [
          'id' => $result->getRawValue(),
          'raw' => $result->getRawValue(),
          'value' => $result->getDisplayValue(),
        ];
      }

      $facetSummaryItem = self::getNestedSummaryValue($result->getChildren(), $rawValue);
      if ($facetSummaryItem !== FALSE) {
        return $facetSummaryItem;
      }
    }

    return FALSE;
  }

  /**
   * Retrieves or stores a processed facet for the current request.
   */
  public static function getProcessedFacet(string $viewId, string $displayId, string $filterId, ?FacetInterface $processedFacet = NULL): ?FacetInterface {
    if ($processedFacet !== NULL) {
      self::$processedFacets[$viewId][$displayId][$filterId] = $processedFacet;
    }

    return self::$processedFacets[$viewId][$displayId][$filterId] ?? NULL;
  }

  /**
   * Marks a view display as having completed the post-execute facet rebuild.
   */
  public static function markViewPostExecuted(string $viewId, string $displayId): void {
    self::$postExecutedViews[$viewId][$displayId] = TRUE;
  }

  /**
   * Indicates whether a view display finished the post-execute facet rebuild.
   */
  public static function hasViewPostExecuted(string $viewId, string $displayId): bool {
    return self::$postExecutedViews[$viewId][$displayId] ?? FALSE;
  }

}
