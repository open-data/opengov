<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\ShowSiblingsProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for ShowSiblingsProcessor.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets\Plugin\facets\processor\ShowSiblingsProcessor
 */
class ShowSiblingsProcessorTest extends UnitTestCase {

  /**
   * The processor under test.
   *
   * @var \Drupal\facets\Plugin\facets\processor\ShowSiblingsProcessor
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->processor = new ShowSiblingsProcessor([], 'show_siblings_processor', []);
  }

  /**
   * Tests support for persisted facet entities.
   *
   * @covers ::supportsFacet
   */
  public function testSupportsFacetEntity(): void {
    $facet = new Facet(['facet_type' => 'facet_entity'], 'facets_facet');

    $this->assertTrue($this->processor->supportsFacet($facet));
  }

  /**
   * Tests support for exposed facet filters.
   *
   * @covers ::supportsFacet
   */
  public function testSupportsExposedFacetFilter(): void {
    $facet = new Facet(['facet_type' => 'facets_exposed_filter'], 'facets_facet');

    $this->assertTrue($this->processor->supportsFacet($facet));
  }

  /**
   * Tests unsupported facet types.
   *
   * @covers ::supportsFacet
   */
  public function testRejectsUnsupportedFacetType(): void {
    $facet = new Facet(['facet_type' => 'custom_facet_type'], 'facets_facet');

    $this->assertFalse($this->processor->supportsFacet($facet));
  }

}
