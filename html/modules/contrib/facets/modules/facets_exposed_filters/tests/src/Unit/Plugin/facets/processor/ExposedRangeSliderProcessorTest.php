<?php

declare(strict_types=1);

namespace Drupal\Tests\facets_exposed_filters\Unit\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets_exposed_filters\Plugin\facets\processor\ExposedRangeSliderProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the exposed range slider processor.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets_exposed_filters\Plugin\facets\processor\ExposedRangeSliderProcessor
 */
final class ExposedRangeSliderProcessorTest extends UnitTestCase {

  /**
   * Tests that active range values are kept when no buckets are returned.
   *
   * @covers ::postQuery
   */
  public function testPostQueryKeepsActiveRangeWithoutResults(): void {
    $processor = new ExposedRangeSliderProcessor(['step' => 1], 'exposed_range_slider', []);
    $facet = $this->createMock(FacetInterface::class);

    $facet
      ->method('getResults')
      ->willReturn([]);
    $facet
      ->method('getActiveItems')
      ->willReturn([['2', '10']]);

    $facet
      ->expects($this->once())
      ->method('setResults')
      ->with($this->callback(static function (array $results): bool {
        return count($results) === 2
          && $results[0] instanceof ResultInterface
          && $results[1] instanceof ResultInterface
          && $results[0]->getRawValue() === '2'
          && $results[1]->getRawValue() === '10'
          && $results[0]->get('facets_exposed_filters_preserve_active_range') === TRUE
          && $results[1]->get('facets_exposed_filters_preserve_active_range') === TRUE;
      }));

    $processor->postQuery($facet);
  }

  /**
   * Tests that empty results without an active range stay untouched.
   *
   * @covers ::postQuery
   */
  public function testPostQueryLeavesEmptyInactiveRangeUntouched(): void {
    $processor = new ExposedRangeSliderProcessor(['step' => 1], 'exposed_range_slider', []);
    $facet = $this->createMock(FacetInterface::class);

    $facet
      ->method('getResults')
      ->willReturn([]);
    $facet
      ->method('getActiveItems')
      ->willReturn([]);

    $facet
      ->expects($this->never())
      ->method('setResults');

    $processor->postQuery($facet);
  }

}
