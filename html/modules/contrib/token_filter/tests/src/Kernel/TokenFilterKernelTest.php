<?php

namespace Drupal\Tests\token_filter\Kernel;

use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Filter module filters individually.
 *
 * @group filter
 */
class TokenFilterKernelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'filter', 'token', 'token_filter'];

  /**
   * An array of all available filters.
   *
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the line break filter.
   */
  public function testGlobalTokenFilter() {
    $filter = $this->filters['token_filter'];

    \Drupal::configFactory()->getEditable('system.site')
      ->set('name', 'Pink flamingo bazaar')
      ->save();

    $input = "Site name is: [site:name]";
    $output = $filter->process($input, 'und');
    $processed = $output->getProcessedText();

    $this->assertEquals($processed, 'Site name is: Pink flamingo bazaar');
  }

}
