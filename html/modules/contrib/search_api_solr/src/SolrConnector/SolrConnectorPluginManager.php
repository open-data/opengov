<?php

namespace Drupal\search_api_solr\SolrConnector;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\State\StateInterface;
use Solarium\Core\Query\Helper;

/**
 * A plugin manager for Solr connector plugins.
 *
 * @see \Drupal\search_api_solr\Annotation\SolrConnector
 * @see \Drupal\search_api_solr\Attribute\SolrConnector
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorInterface
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase
 *
 * @ingroup plugin_api
 */
class SolrConnectorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a SolrConnectorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->alterInfo('search_api_solr_connector_info');
    $this->setCacheBackend($cache_backend, 'search_api_solr_connector_plugins');

    parent::__construct(
      'Plugin/SolrConnector',
      $namespaces,
      $module_handler,
      'Drupal\search_api_solr\SolrConnectorInterface',
      'Drupal\search_api_solr\Attribute\SolrConnector',
      'Drupal\search_api_solr\Annotation\SolrConnector',
    );
  }

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface|null
   */
  protected ?StateInterface $state = NULL;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|null
   */
  protected ?DateFormatterInterface $dateFormatter = NULL;

  /**
   * The Solarium query helper.
   *
   * @var \Solarium\Core\Query\Helper|null
   */
  protected ?Helper $queryHelper = NULL;

  /**
   * Sets supporting services for connector instances.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Solarium\Core\Query\Helper $queryHelper
   *   The Solarium query helper.
   */
  public function setConnectorServices(StateInterface $state, DateFormatterInterface $dateFormatter, Helper $queryHelper): void {
    $this->state = $state;
    $this->dateFormatter = $dateFormatter;
    $this->queryHelper = $queryHelper;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFactory() : FactoryInterface {
    if (!$this->factory) {
      assert($this->state instanceof StateInterface);
      assert($this->dateFormatter instanceof DateFormatterInterface);
      assert($this->queryHelper instanceof Helper);
      $this->factory = new SolrConnectorFactory($this, $this->pluginInterface, $this->state, $this->dateFormatter, $this->queryHelper);
    }

    return $this->factory;
  }

}
