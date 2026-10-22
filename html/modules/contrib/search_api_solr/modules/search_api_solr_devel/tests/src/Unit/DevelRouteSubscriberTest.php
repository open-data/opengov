<?php

namespace Drupal\Tests\search_api_solr_devel\Unit;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\search_api_solr_devel\Routing\DevelRouteSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * Tests devel Solr route generation.
 *
 * @group search_api_solr
 *
 * @coversDefaultClass \Drupal\search_api_solr_devel\Routing\DevelRouteSubscriber
 */
class DevelRouteSubscriberTest extends UnitTestCase {

  /**
   * Tests that route alterations run after Devel's route subscriber.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents(): void {
    $events = DevelRouteSubscriber::getSubscribedEvents();

    $this->assertSame(['onAlterRoutes', 100], $events[RoutingEvents::ALTER]);
  }

  /**
   * Tests generated route metadata for entities with a devel-solr link.
   *
   * @covers ::getDevelSolrRoute
   */
  public function testGetDevelSolrRoute(): void {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getLinkTemplate')
      ->with('devel-solr')
      ->willReturn('/node/{node}/devel/solr');
    $entity_type->method('id')->willReturn('node');

    $subscriber = $this->createRouteSubscriber();
    $route = $subscriber->getRoute($entity_type);

    $this->assertSame('/node/{node}/devel/solr', $route->getPath());
    $this->assertSame(
      '\Drupal\search_api_solr_devel\Controller\DevelController::entitySolr',
      $route->getDefault('_controller')
    );
    $this->assertSame('Devel Solr', $route->getDefault('_title'));
    $this->assertSame('access devel information', $route->getRequirement('_permission'));
    $this->assertTrue($route->getOption('_admin_route'));
    $this->assertSame('node', $route->getOption('_devel_entity_type_id'));
    $this->assertSame([
      'node' => ['type' => 'entity:node'],
    ], $route->getOption('parameters'));
  }

  /**
   * Tests that no route is generated without a devel-solr link template.
   *
   * @covers ::getDevelSolrRoute
   */
  public function testGetDevelSolrRouteWithoutTemplate(): void {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getLinkTemplate')
      ->with('devel-solr')
      ->willReturn(NULL);

    $subscriber = $this->createRouteSubscriber();

    $this->assertNull($subscriber->getRoute($entity_type));
  }

  /**
   * Creates a route subscriber that exposes protected route generation.
   */
  private function createRouteSubscriber(): TestDevelRouteSubscriber {
    return new TestDevelRouteSubscriber($this->createMock(EntityTypeManagerInterface::class));
  }

}

/**
 * Exposes protected route generation for testing.
 */
class TestDevelRouteSubscriber extends DevelRouteSubscriber {

  /**
   * Returns the generated devel Solr route.
   */
  public function getRoute(EntityTypeInterface $entity_type) {
    return $this->getDevelSolrRoute($entity_type);
  }

}
