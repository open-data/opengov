<?php

namespace Drupal\Tests\search_api\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Event\MappingViewsFieldHandlersEvent;
use Drupal\search_api\Event\MappingViewsHandlersEvent;
use Drupal\search_api\Hook\SearchApiViewsHooks;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Tests caching in the Views hooks class.
 *
 * @group search_api
 *
 * @coversDefaultClass \Drupal\search_api\Hook\SearchApiViewsHooks
 */
#[RunTestsInSeparateProcesses]
class ViewsHooksStaticCachingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'filter',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'search_api_test_example_content',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * The events triggered in this test.
   *
   * @var list<class-string<\Drupal\Component\EventDispatcher\Event>>
   */
  protected array $triggeredEvents = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);

    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');

    $this->installConfig([
      'search_api',
      'search_api_test_example_content',
      'search_api_test_db',
    ]);
  }

  /**
   * Tests static caching for the handler mappings.
   *
   * @covers ::getHandlerMapping
   * @covers ::getFieldHandlerMapping
   */
  public function testStaticCaching(): void {
    $event_dispatcher = $this->createStub(EventDispatcherInterface::class);
    $event_dispatcher->method('dispatch')
      ->willReturnCallback(function (object $event) {
        $this->triggeredEvents[] = get_class($event);
      });

    $hooks = \Drupal::service(SearchApiViewsHooks::class);
    /* @see SearchApiViewsHooks::$eventDispatcher */
    (new \ReflectionProperty(SearchApiViewsHooks::class, 'eventDispatcher'))
      ->setValue($hooks, $event_dispatcher);

    $hooks->viewsData();

    // If static caching worked then each of the events should have been fired
    // only once.
    $this->assertEquals([
      MappingViewsHandlersEvent::class,
      MappingViewsFieldHandlersEvent::class,
    ], $this->triggeredEvents);
  }

}
