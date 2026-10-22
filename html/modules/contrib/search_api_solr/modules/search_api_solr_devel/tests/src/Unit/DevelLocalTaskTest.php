<?php

namespace Drupal\Tests\search_api_solr_devel\Unit;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\search_api_solr_devel\Plugin\Derivative\DevelLocalTask;
use Drupal\Tests\UnitTestCase;

/**
 * Tests devel local task derivatives.
 *
 * @group search_api_solr
 *
 * @coversDefaultClass \Drupal\search_api_solr_devel\Plugin\Derivative\DevelLocalTask
 */
class DevelLocalTaskTest extends UnitTestCase {

  /**
   * Tests that Solr local tasks are created only for supported entity types.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions(): void {
    $node_type = $this->createMock(EntityTypeInterface::class);
    $node_type->method('hasLinkTemplate')
      ->with('devel-solr')
      ->willReturn(TRUE);

    $user_type = $this->createMock(EntityTypeInterface::class);
    $user_type->method('hasLinkTemplate')
      ->with('devel-solr')
      ->willReturn(FALSE);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinitions')->willReturn([
      'node' => $node_type,
      'user' => $user_type,
    ]);

    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')->willReturn('Solr');

    $deriver = new DevelLocalTask($entity_type_manager, $translation);
    $definitions = $deriver->getDerivativeDefinitions([
      'base_key' => 'base_value',
    ]);

    $this->assertSame(['node.devel_solr_tab'], array_keys($definitions));
    $this->assertSame('entity.node.devel_solr', $definitions['node.devel_solr_tab']['route_name']);
    $this->assertSame(110, $definitions['node.devel_solr_tab']['weight']);
    $this->assertSame('Solr', (string) $definitions['node.devel_solr_tab']['title']);
    $this->assertSame('devel.entities:node.devel_tab', $definitions['node.devel_solr_tab']['parent_id']);
    $this->assertSame('base_value', $definitions['node.devel_solr_tab']['base_key']);
  }

}
