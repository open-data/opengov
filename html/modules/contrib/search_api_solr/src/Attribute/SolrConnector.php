<?php

namespace Drupal\search_api_solr\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a connector plugin attribute object.
 *
 * Condition plugins provide generalized conditions for use in other
 * operations, such as conditional block placement.
 *
 * Plugin Namespace: Plugin\SolrConnector
 *
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorManager
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorInterface
 * @see \Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase
 *
 * @ingroup plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SolrConnector extends Plugin {

  /**
   * Constructs a new attribute instance.
   *
   * @param string $id
   *   The suggester plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   (optional) The human-readable name of the connector plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   (optional) The connector description.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
  ) {}

}
