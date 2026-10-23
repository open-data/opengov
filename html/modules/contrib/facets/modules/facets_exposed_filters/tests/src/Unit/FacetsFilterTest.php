<?php

declare(strict_types=1);

namespace Drupal\Tests\facets_exposed_filters\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets_exposed_filters\FacetsExposedFiltersHelper;
use Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;

/**
 * Tests exposed-filter value handling for facet results.
 *
 * @group facets
 */
final class FacetsFilterTest extends UnitTestCase {

  /**
   * Tests that an exposed-filter block without a Views query remains empty.
   */
  public function testValueFormWithoutViewsQueryReturnsEmptyForm(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();
    $filter->options = [
      'id' => 'facets_keywords',
      'facet' => [
        'depends_on_exposed_filter' => '',
      ],
    ];

    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('test_no_views_query');
    $view->current_display = 'default';
    $view->filter = ['facets_keywords' => $filter];
    $filter->view = $view;

    FacetsExposedFiltersHelper::markViewPostExecuted('test_no_views_query', 'default');

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    self::assertSame(
      ['value' => []],
      $filter->valueForm($form, $form_state),
    );
  }

  /**
   * Tests that missing results use the encoded missing-filter syntax.
   */
  public function testMissingResultUsesEncodedExposedValue(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $result = $this->createMock(ResultInterface::class);
    $url_processor = new class() {

      /**
       * Returns the query-string delimiter.
       */
      public function getDelimiter(): string {
        return ',';
      }

    };
    $url_processor_handler = new class($url_processor) {

      /**
       * Creates the handler stub.
       *
       * @param object $processor
       *   The URL processor stub.
       */
      public function __construct(
        private readonly object $processor,
      ) {}

      /**
       * Returns the URL processor.
       */
      public function getProcessor(): object {
        return $this->processor;
      }

    };

    $facet
      ->method('getProcessors')
      ->willReturn(['url_processor_handler' => $url_processor_handler]);
    $result
      ->method('isMissing')
      ->willReturn(TRUE);
    $result
      ->method('getMissingFilters')
      ->willReturn(['apple', 'pear']);
    $result
      ->method('getRawValue')
      ->willReturn('!');

    $value = $this->invokePrivateMethod($filter, 'getExposedOptionValue', [$facet, $result]);

    self::assertSame('!(apple,pear)', $value);
  }

  /**
   * Tests that an active missing token is preserved for checkbox matching.
   */
  public function testActiveMissingResultKeepsSubmittedToken(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $result = $this->createMock(ResultInterface::class);

    $facet
      ->method('getActiveItems')
      ->willReturn(['!(apple,pear)']);
    $facet
      ->method('getProcessors')
      ->willReturn([]);
    $result
      ->method('isMissing')
      ->willReturn(TRUE);
    $result
      ->method('getMissingFilters')
      ->willReturn(['apple', 'pear']);
    $result
      ->method('getRawValue')
      ->willReturn('!');

    $value = $this->invokePrivateMethod($filter, 'getExposedOptionValue', [$facet, $result]);

    self::assertSame('!(apple,pear)', $value);
  }

  /**
   * Tests that show-only-one-result hides inactive options once selected.
   */
  public function testShowOnlyOneResultKeepsOnlyMatchingOption(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $active_result = $this->createMock(ResultInterface::class);
    $inactive_result = $this->createMock(ResultInterface::class);

    $facet
      ->method('getShowOnlyOneResult')
      ->willReturn(TRUE);
    $facet
      ->method('getActiveItems')
      ->willReturn(['6036']);
    $active_result
      ->method('getChildren')
      ->willReturn([]);
    $active_result
      ->method('isMissing')
      ->willReturn(FALSE);
    $active_result
      ->method('getRawValue')
      ->willReturn('6036');
    $inactive_result
      ->method('getChildren')
      ->willReturn([]);
    $inactive_result
      ->method('isMissing')
      ->willReturn(FALSE);
    $inactive_result
      ->method('getRawValue')
      ->willReturn('6037');

    $results = $this->invokePrivateMethod(
      $filter,
      'applyShowOnlyOneResult',
      [$facet, [$active_result, $inactive_result]]
    );

    self::assertSame([$active_result], array_values($results));
  }

  /**
   * Tests that show-only-one-result uses the submitted option value.
   */
  public function testShowOnlyOneResultKeepsMatchingSubmittedValue(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $matching_result = $this->createMock(ResultInterface::class);
    $other_result = $this->createMock(ResultInterface::class);

    $facet
      ->method('getShowOnlyOneResult')
      ->willReturn(TRUE);
    $facet
      ->method('getActiveItems')
      ->willReturn(['6036']);

    $matching_result
      ->method('getChildren')
      ->willReturn([]);
    $matching_result
      ->method('isMissing')
      ->willReturn(FALSE);
    $matching_result
      ->method('getRawValue')
      ->willReturn('6036');

    $other_result
      ->method('getChildren')
      ->willReturn([]);
    $other_result
      ->method('isMissing')
      ->willReturn(FALSE);
    $other_result
      ->method('getRawValue')
      ->willReturn('6037');

    $results = $this->invokePrivateMethod($filter, 'applyShowOnlyOneResult', [$facet, [$matching_result, $other_result]]);

    self::assertSame([$matching_result], array_values($results));
  }

  /**
   * Tests that all options stay visible when nothing is selected.
   */
  public function testShowOnlyOneResultLeavesResultsWhenInactive(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $first_result = $this->createMock(ResultInterface::class);
    $second_result = $this->createMock(ResultInterface::class);

    $facet
      ->method('getShowOnlyOneResult')
      ->willReturn(TRUE);
    $facet
      ->method('getActiveItems')
      ->willReturn([]);

    $first_result
      ->method('getChildren')
      ->willReturn([]);
    $second_result
      ->method('getChildren')
      ->willReturn([]);

    $results = $this->invokePrivateMethod($filter, 'applyShowOnlyOneResult', [$facet, [$first_result, $second_result]]);

    self::assertSame([$first_result, $second_result], $results);
  }

  /**
   * Tests that final exposed options collapse to the submitted values.
   */
  public function testShowOnlyOneResultFiltersRenderedOptions(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $facet
      ->method('getShowOnlyOneResult')
      ->willReturn(TRUE);
    $facet
      ->method('getActiveItems')
      ->willReturn(['6036']);

    $options = $this->invokePrivateMethod($filter, 'filterOptionsByActiveValues', [
      $facet,
      [
        '6034' => 'Anthracite (1)',
        '6036' => 'Black (107)',
        '6037' => 'Blue (1)',
      ],
    ]);

    self::assertSame(['6036' => 'Black (107)'], $options);
  }

  /**
   * Tests exposed range key detection for min/max values.
   */
  public function testHasExposedRangeKeysDetectsMinMaxValues(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $has_range_keys = $this->invokePrivateMethod($filter, 'hasExposedRangeKeys', [
      [
        'min' => '2',
        'max' => '10',
      ],
    ]);

    self::assertTrue($has_range_keys);
  }

  /**
   * Tests exposed range key detection for normalized range tuples.
   */
  public function testHasExposedRangeKeysDetectsRangeTuple(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $has_range_keys = $this->invokePrivateMethod($filter, 'hasExposedRangeKeys', [[['2', '10']]]);

    self::assertTrue($has_range_keys);
  }

  /**
   * Invokes a private method on the subject.
   *
   * @param object $subject
   *   The subject instance.
   * @param string $method_name
   *   The private method name.
   * @param array<int, mixed> $arguments
   *   Method arguments.
   *
   * @return mixed
   *   The method result.
   */
  private function invokePrivateMethod(object $subject, string $method_name, array $arguments = []): mixed {
    $reflection = new \ReflectionObject($subject);
    $method = $reflection->getMethod($method_name);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($subject, $arguments);
  }

}
