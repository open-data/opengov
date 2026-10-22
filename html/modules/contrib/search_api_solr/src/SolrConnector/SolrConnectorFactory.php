<?php

namespace Drupal\search_api_solr\SolrConnector;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Solarium\Core\Query\Helper;

/**
 * SolrConnector plugin factory.
 */
class SolrConnectorFactory extends ContainerFactory {

  /**
   * Cached connector instances keyed by configuration hash.
   *
   * @var \Drupal\search_api_solr\SolrConnectorInterface[]
   */
  protected static array $instances = [];

  /**
   * Constructs a SolrConnectorFactory object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The plugin discovery.
   * @param string|null $pluginInterface
   *   The plugin interface.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Solarium\Core\Query\Helper $queryHelper
   *   The Solarium query helper.
   */
  public function __construct(
    DiscoveryInterface $discovery,
    $pluginInterface,
    protected StateInterface $state,
    protected DateFormatterInterface $dateFormatter,
    protected Helper $queryHelper,
  ) {
    parent::__construct($discovery, $pluginInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    ksort($configuration);

    $configuration_hash = md5($plugin_id . json_encode($configuration));

    if (!isset(self::$instances[$configuration_hash])) {
      $connector = parent::createInstance($plugin_id, $configuration);
      assert($connector instanceof SolrConnectorPluginBase);
      $connector
        ->setState($this->state)
        ->setDateFormatter($this->dateFormatter)
        ->setQueryHelper($this->queryHelper);
      self::$instances[$configuration_hash] = $connector;
    }

    return self::$instances[$configuration_hash];
  }

}
