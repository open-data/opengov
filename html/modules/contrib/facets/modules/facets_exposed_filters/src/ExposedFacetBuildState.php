<?php

namespace Drupal\facets_exposed_filters;

use Drupal\facets\FacetInterface;

/**
 * Stores request-local skip decisions for exposed facet building.
 */
final class ExposedFacetBuildState {

  /**
   * Facet IDs that should be skipped for the current request.
   *
   * @var array<string, bool>
   */
  protected static array $skippedFacetIds = [];

  /**
   * Marks an exposed facet as skipped for the current request.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to skip.
   */
  public static function skipFacet(FacetInterface $facet): void {
    self::$skippedFacetIds[$facet->id()] = TRUE;
  }

  /**
   * Clears a previous skip decision for an exposed facet.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to keep buildable.
   */
  public static function allowFacet(FacetInterface $facet): void {
    unset(self::$skippedFacetIds[$facet->id()]);
  }

  /**
   * Checks whether the exposed facet should be skipped.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to check.
   *
   * @return bool
   *   TRUE when the facet should be skipped.
   */
  public static function shouldSkipFacet(FacetInterface $facet): bool {
    return !empty(self::$skippedFacetIds[$facet->id()]);
  }

}
