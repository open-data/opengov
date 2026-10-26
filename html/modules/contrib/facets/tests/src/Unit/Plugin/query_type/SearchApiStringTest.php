<?php

namespace Drupal\Tests\facets\Unit\Plugin\query_type;

use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\query_type\SearchApiString;
use Drupal\facets\Result\ResultInterface;
use Drupal\search_api\Query\ConditionGroup;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for query type.
 *
 * @group facets
 */
class SearchApiStringTest extends UnitTestCase {

  /**
   * Tests string query type without executing the query with an "AND" operator.
   */
  public function testQueryTypeAnd() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'and'],
      'facets_facet'
    );

    $original_results = [
      ['count' => 3, 'filter' => 'badger'],
      ['count' => 5, 'filter' => 'mushroom'],
      ['count' => 7, 'filter' => 'narwhal'],
      ['count' => 9, 'filter' => 'unicorn'],
    ];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertSame('array', gettype($results));

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf(ResultInterface::class, $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests string query type without executing the query with an "OR" operator.
   */
  public function testQueryTypeOr() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'or'],
      'facets_facet'
    );
    $facet->setFieldIdentifier('field_animal');

    $original_results = [
      ['count' => 3, 'filter' => 'badger'],
      ['count' => 5, 'filter' => 'mushroom'],
      ['count' => 7, 'filter' => 'narwhal'],
      ['count' => 9, 'filter' => 'unicorn'],
    ];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertSame('array', gettype($results));

    foreach ($original_results as $k => $result) {
      $this->assertInstanceOf(ResultInterface::class, $results[$k]);
      $this->assertEquals($result['count'], $results[$k]->getCount());
      $this->assertEquals($result['filter'], $results[$k]->getDisplayValue());
    }
  }

  /**
   * Tests string query type without results.
   */
  public function testEmptyResults() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertSame('array', gettype($results));
    $this->assertEmpty($results);
  }

  /**
   * Tests string query type without results.
   */
  public function testConfiguration() {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $default_config = ['facet' => $facet, 'query' => $query];
    $query_type = new SearchApiString($default_config, 'search_api_string', []);

    $this->assertEquals([], $query_type->defaultConfiguration());
    $this->assertEquals($default_config, $query_type->getConfiguration());

    $query_type->setConfiguration(['owl' => 'Long-eared owl']);
    $this->assertEquals(['owl' => 'Long-eared owl'], $query_type->getConfiguration());
  }

  /**
   * Tests trimming in ::build.
   *
   * @dataProvider provideTrimValues
   */
  public function testTrim($expected_value, $input_value) {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet([], 'facets_facet');

    $original_results = [['count' => 1, 'filter' => $input_value]];

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => $original_results,
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $this->assertInstanceOf(FacetInterface::class, $built_facet);

    $results = $built_facet->getResults();
    $this->assertSame('array', gettype($results));

    $this->assertInstanceOf(ResultInterface::class, $results[0]);
    $this->assertEquals(1, $results[0]->getCount());
    $this->assertEquals($expected_value, $results[0]->getDisplayValue());
  }

  /**
   * Data provider for ::provideTrimValues.
   *
   * @return array
   *   An array of expected and input values.
   */
  public static function provideTrimValues() {
    return [
      ['owl', '"owl"'],
      ['owl', 'owl'],
      ['owl', '"owl'],
      ['owl', 'owl"'],
      ['"owl', '""owl"'],
      ['owl"', '"owl""'],
    ];
  }

  /**
   * Tests that missing buckets always narrow with AND semantics.
   */
  public function testExecuteMissingItemUsesAndSubgroup(): void {
    $facet = new Facet(
      ['query_operator' => 'or'],
      'facets_facet'
    );
    $facet->setFieldIdentifier('field_animal');
    $facet->setActiveItems(['!(badger,mushroom)']);
    $facet->addProcessor([
      'processor_id' => 'url_processor_handler',
      'weights' => [],
      'settings' => [],
    ]);

    $url_processor = new class {

      /**
       * Returns the facet URL delimiter.
       */
      public function getDelimiter(): string {
        return ',';
      }

    };

    $url_processor_handler = new class($url_processor) {

      public function __construct(private readonly object $processor) {
      }

      /**
       * Returns the configured URL processor.
       */
      public function getProcessor(): object {
        return $this->processor;
      }

    };

    $processors_property = new \ReflectionProperty(Facet::class, 'processors');
    $processors_property->setValue($facet, [
      'url_processor_handler' => $url_processor_handler,
    ]);

    $captured_group = NULL;
    $query = $this->createMock(QueryInterface::class);
    $query->method('getProcessingLevel')
      ->willReturn(QueryInterface::PROCESSING_BASIC);
    $query->method('createConditionGroup')
      ->willReturnCallback(static fn (string $conjunction = 'AND', array $tags = []): ConditionGroupInterface => new ConditionGroup($conjunction, $tags));
    $query->expects($this->once())
      ->method('addConditionGroup')
      ->willReturnCallback(static function (ConditionGroupInterface $condition_group) use (&$captured_group): void {
        $captured_group = $condition_group;
      });

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_string',
      []
    );

    $query_type->execute();

    $this->assertInstanceOf(ConditionGroupInterface::class, $captured_group);
    $this->assertSame('OR', $captured_group->getConjunction());

    $conditions = $captured_group->getConditions();
    $this->assertCount(1, $conditions);
    $this->assertInstanceOf(ConditionGroupInterface::class, $conditions[0]);
    $this->assertSame('AND', $conditions[0]->getConjunction());

    $missing_conditions = $conditions[0]->getConditions();
    $this->assertCount(2, $missing_conditions);
    $this->assertSame('field_animal', $missing_conditions[0]->getField());
    $this->assertSame('badger', $missing_conditions[0]->getValue());
    $this->assertSame('<>', $missing_conditions[0]->getOperator());
    $this->assertSame('mushroom', $missing_conditions[1]->getValue());
    $this->assertSame('<>', $missing_conditions[1]->getOperator());
  }

  /**
   * Tests that missing buckets also work without a URL processor handler.
   */
  public function testExecuteMissingItemWithoutUrlProcessorHandler(): void {
    $facet = new Facet(
      ['query_operator' => 'and'],
      'facets_facet'
    );
    $facet->setFieldIdentifier('field_animal');
    $facet->setActiveItems(['!(badger,mushroom)']);
    $processors_property = new \ReflectionProperty(Facet::class, 'processors');
    $processors_property->setValue($facet, []);

    $captured_group = NULL;
    $query = $this->createMock(QueryInterface::class);
    $query->method('getProcessingLevel')
      ->willReturn(QueryInterface::PROCESSING_BASIC);
    $query->method('createConditionGroup')
      ->willReturnCallback(static fn (string $conjunction = 'AND', array $tags = []): ConditionGroupInterface => new ConditionGroup($conjunction, $tags));
    $query->expects($this->once())
      ->method('addConditionGroup')
      ->willReturnCallback(static function (ConditionGroupInterface $condition_group) use (&$captured_group): void {
        $captured_group = $condition_group;
      });

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
      ],
      'search_api_string',
      []
    );

    $query_type->execute();

    $this->assertInstanceOf(ConditionGroupInterface::class, $captured_group);
    $conditions = $captured_group->getConditions();
    $this->assertCount(1, $conditions);
    $this->assertInstanceOf(ConditionGroupInterface::class, $conditions[0]);
    $missing_conditions = $conditions[0]->getConditions();
    $this->assertCount(2, $missing_conditions);
    $this->assertSame('badger', $missing_conditions[0]->getValue());
    $this->assertSame('mushroom', $missing_conditions[1]->getValue());
  }

  /**
   * Tests that active missing tokens map back to the missing result.
   */
  public function testBuildKeepsMissingLabelForActiveMissingToken(): void {
    $query = new SearchApiQuery([], 'search_api_query', []);
    $facet = new Facet(
      ['query_operator' => 'and', 'missing' => TRUE, 'missing_label' => 'others'],
      'facets_facet'
    );
    $facet->setActiveItems(['!(badger,mushroom)']);

    $query_type = new SearchApiString(
      [
        'facet' => $facet,
        'query' => $query,
        'results' => [
          ['count' => 4, 'filter' => '!'],
          ['count' => 2, 'filter' => 'narwhal'],
        ],
      ],
      'search_api_string',
      []
    );

    $built_facet = $query_type->build();
    $results = $built_facet->getResults();

    $this->assertCount(2, $results);
    $this->assertSame('!', $results[0]->getRawValue());
    $this->assertTrue($results[0]->isMissing());
    $this->assertTrue($results[0]->isActive());
    $this->assertSame(['narwhal'], array_values($results[0]->getMissingFilters()));
  }

}
