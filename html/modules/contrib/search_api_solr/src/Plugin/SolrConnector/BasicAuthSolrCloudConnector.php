<?php

namespace Drupal\search_api_solr\Plugin\SolrConnector;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api_solr\Attribute\SolrConnector;
use Drupal\search_api_solr\SolrConnector\BasicAuthTrait;

/**
 * Basic auth Solr connector.
 */
#[SolrConnector(
  id: 'solr_cloud_basic_auth',
  label: new TranslatableMarkup('Solr Cloud with Basic Auth'),
  description: new TranslatableMarkup('A connector usable for Solr Cloud installations protected by basic authentication.'),
)]
class BasicAuthSolrCloudConnector extends StandardSolrCloudConnector {

  use BasicAuthTrait;

  /**
   * {@inheritdoc}
   */
  public function isTrustedContextSupported() {
    return TRUE;
  }

}
