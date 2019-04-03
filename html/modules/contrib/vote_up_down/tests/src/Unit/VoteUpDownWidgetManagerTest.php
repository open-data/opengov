<?php

namespace Drupal\Tests\vud\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vud\Plugin\VoteUpDownWidgetManager;

/**
 * A Unit test to check if the plugins are working fine.
 *
 * @covers \Drupal\vud\Plugin\VoteUpDownWidgetManager
 *
 * @group vud_widget
 */
class VoteUpDownWidgetManagerTest extends UnitTestCase {

  /**
   * Plugin Manager for VoteUpDownWidget plugin type under test.
   */
  protected $voteUpDownWidgetManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $cache_backend = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $this->voteUpDownWidgetManager = new VoteUpDownWidgetManager(new \ArrayObject(), $cache_backend->reveal(), $module_handler->reveal());

    $discovery = $this->prophesize(DiscoveryInterface::class);

    $discovery->getDefinitions()->willReturn([
      'newPlugin' => [
        'id' => 'new_plugin',
        'admin_label' => @t('New Plugin'),
        'description' => 'New plugin type'
      ],
    ]);

    $property = new \ReflectionProperty(VoteUpDownWidgetManager::class, 'discovery');
    $property->setAccessible(TRUE);
    $property->setValue($this->voteUpDownWidgetManager, $discovery->reveal());
  }

  /**
   * Tests if the plugin created by the test is same as that of the original definition.
   */
  public function testDefinitions() {
    $definitions = $this->voteUpDownWidgetManager->getDefinitions();
    $this->assertSame(['newPlugin'], array_keys($definitions));
  }

}
