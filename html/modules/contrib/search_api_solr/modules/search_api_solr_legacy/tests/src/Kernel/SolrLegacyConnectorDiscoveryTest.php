<?php

namespace Drupal\Tests\search_api_solr_legacy\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests discovery of legacy Solr connector plugins.
 *
 * @group search_api_solr_legacy
 */
class SolrLegacyConnectorDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api_solr',
    'search_api_solr_legacy',
    'search_api_solr_legacy_test',
  ];

  /**
   * Ensures legacy connector plugins remain discoverable.
   */
  public function testLegacyConnectorDiscovery(): void {
    $plugin_manager = $this->container->get('plugin.manager.search_api_solr.connector');

    $this->assertSame('solr_36', $plugin_manager->getDefinition('solr_36')['id']);
    $this->assertSame('solr_36_test', $plugin_manager->getDefinition('solr_36_test')['id']);
  }

}
