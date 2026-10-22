<?php

declare(strict_types=1);

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\facets\Entity\Facet;
use Drupal\facets\Hierarchy\HierarchyInterface;
use Drupal\facets\Plugin\facets\processor\HideInactiveSiblingsProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for HideInactiveSiblingsProcessor.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets\Plugin\facets\processor\HideInactiveSiblingsProcessor
 */
class HideInactiveSiblingsProcessorTest extends UnitTestCase {

  /**
   * The processor under test.
   *
   * @var \Drupal\facets\Plugin\facets\processor\HideInactiveSiblingsProcessor
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->processor = new HideInactiveSiblingsProcessor([], 'hide_inactive_siblings_processor', []);
  }

  /**
   * Tests facet support for persisted facet entities.
   *
   * @covers ::supportsFacet
   */
  public function testSupportsFacetEntity(): void {
    $facet = new Facet(['facet_type' => 'facet_entity'], 'facets_facet');

    $this->assertTrue($this->processor->supportsFacet($facet));
  }

  /**
   * Tests facet support for exposed facet filters.
   *
   * @covers ::supportsFacet
   */
  public function testSupportsExposedFacetFilter(): void {
    $facet = new Facet(['facet_type' => 'facets_exposed_filter'], 'facets_facet');

    $this->assertTrue($this->processor->supportsFacet($facet));
  }

  /**
   * Tests unsupported custom facet types.
   *
   * @covers ::supportsFacet
   */
  public function testRejectsUnsupportedFacetType(): void {
    $facet = new Facet(['facet_type' => 'custom_facet_type'], 'facets_facet');

    $this->assertFalse($this->processor->supportsFacet($facet));
  }

  /**
   * Tests that parents with active children are not removed.
   *
   * @covers ::build
   */
  public function testBuildKeepsParentsWithActiveChildren(): void {
    $facet = $this->createMock(Facet::class);
    $hierarchy = $this->createMock(HierarchyInterface::class);

    $facet->method('getActiveItems')->willReturn(['min_2']);
    $facet->method('getUseHierarchy')->willReturn(TRUE);
    $facet->method('getHierarchyInstance')->willReturn($hierarchy);
    $facet->method('getKeepHierarchyParentsActive')->willReturn(FALSE);
    $facet->expects($this->once())->method('addCacheableDependency')->with($hierarchy);

    $hierarchy->method('getParentIds')->with('min_2')->willReturn(['usb_a']);
    $hierarchy->method('getSiblingIds')->with(['min_2', 'usb_a'])->willReturn(['usb_a', 'usb_c']);
    $hierarchy->method('getChildIds')->with(['usb_a', 'usb_c'])->willReturn([
      'usb_a' => ['min_2', 'min_3'],
      'usb_c' => [],
    ]);

    $parent = new Result($facet, 'usb_a', 'USB-A', 2);
    $active_child = new Result($facet, 'min_2', 'at least 2', 2);
    $active_child->setActiveState(TRUE);
    $inactive_child = new Result($facet, 'min_3', 'at least 3', 1);
    $parent->setChildren([$active_child, $inactive_child]);
    $sibling = new Result($facet, 'usb_c', 'USB-C', 4);

    $results = [
      'usb_a' => $parent,
      'usb_c' => $sibling,
    ];

    $facet->method('getResults')->willReturn($results);

    $build = $this->processor->build($facet, $results);

    $this->assertArrayHasKey('usb_a', $build);
    $this->assertArrayNotHasKey('usb_c', $build);
  }

  /**
   * Tests that range-slider active values are ignored for hierarchy lookups.
   *
   * @covers ::build
   */
  public function testBuildIgnoresNonScalarActiveItemsForHierarchy(): void {
    $facet = $this->createMock(Facet::class);
    $hierarchy = $this->createMock(HierarchyInterface::class);

    $facet->method('getActiveItems')->willReturn([[2, 10]]);
    $facet->method('getUseHierarchy')->willReturn(TRUE);
    $facet->method('getHierarchyInstance')->willReturn($hierarchy);
    $facet->method('getKeepHierarchyParentsActive')->willReturn(FALSE);
    $facet->expects($this->once())->method('addCacheableDependency')->with($hierarchy);

    $hierarchy->expects($this->never())->method('getParentIds');
    $hierarchy->method('getSiblingIds')->with([])->willReturn([]);
    $hierarchy->method('getChildIds')->with([])->willReturn([]);

    $result = new Result($facet, 'battery_capacity', 'Battery capacity', 2);
    $results = ['battery_capacity' => $result];

    $facet->method('getResults')->willReturn($results);

    $this->assertSame($results, $this->processor->build($facet, $results));
  }

}
