<?php

namespace Drupal\search_api_solr\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\search_api_solr\TypedData\SolrDocumentDefinition;

/**
 * Defines the "Solr document" data type.
 *
 * Instances of this class wrap Search API Item objects and allow to deal with
 * items based upon the Typed Data API.
 */
#[DataType(
  id: 'solr_multisite_document',
  label: new TranslatableMarkup('Solr multisite document'),
  description: new TranslatableMarkup('Records from a Solr multisite index.'),
  definition_class: SolrDocumentDefinition::class,
)]
class SolrMultisiteDocument extends SolrDocument {

  /**
   * Field name.
   *
   * @var string
   */
  protected $solrField = 'solr_multisite_field';

  /**
   * Document name.
   *
   * @var string
   */
  protected $solrDocument = 'solr_multisite_document';

}
