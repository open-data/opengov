<?php

namespace Drupal\Tests\search_api_solr_devel\Unit;

use Drupal\devel\DevelDumperManagerInterface;
use Drupal\search_api_solr_devel\Logging\SolariumRequestLogger;
use Drupal\Tests\UnitTestCase;
use Solarium\Core\Event\Events as SolariumEvents;

/**
 * Tests the Solarium request logger.
 *
 * @group search_api_solr
 *
 * @coversDefaultClass \Drupal\search_api_solr_devel\Logging\SolariumRequestLogger
 */
class SolariumRequestLoggerTest extends UnitTestCase {

  /**
   * Tests Solarium event subscriptions.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents(): void {
    $events = SolariumRequestLogger::getSubscribedEvents();

    $this->assertSame([
      ['postCreateQuery'],
    ], $events[SolariumEvents::POST_CREATE_QUERY]);
    $this->assertSame([
      ['preExecuteRequest'],
    ], $events[SolariumEvents::PRE_EXECUTE_REQUEST]);
    $this->assertSame([
      ['postExecuteRequest'],
    ], $events[SolariumEvents::POST_EXECUTE_REQUEST]);
  }

  /**
   * Tests that Solr admin handlers are ignored.
   *
   * @covers ::shouldIgnore
   */
  public function testShouldIgnore(): void {
    $logger = new SolariumRequestLogger($this->createMock(DevelDumperManagerInterface::class));

    $this->assertEquals(1, $logger->shouldIgnore('admin/ping'));
    $this->assertEquals(1, $logger->shouldIgnore('solr/admin/info/system'));
    $this->assertEquals(0, $logger->shouldIgnore('select'));
    $this->assertEquals(0, $logger->shouldIgnore('update'));
  }

}
