<?php

declare(strict_types=1);

namespace Drupal\Tests\facets\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Facets item-list preprocessing.
 *
 * @group facets
 */
final class FacetsItemListPreprocessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['facets', 'system'];

  /**
   * Tests that item-list preprocessing updates the original variables.
   */
  public function testItemListContentsAreRendered(): void {
    $build = [
      '#theme' => 'facets_item_list',
      '#facet' => NULL,
      '#items' => ['Item 1', 'Item 2'],
    ];

    $renderer = $this->container->get('renderer');
    $output = $renderer->executeInRenderContext(
      new RenderContext(),
      static fn () => $renderer->render($build),
    );

    self::assertStringContainsString('Item 1', (string) $output);
    self::assertStringContainsString('Item 2', (string) $output);
  }

}
