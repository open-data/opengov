<?php

namespace Drupal\search_api_solr\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\search_api_solr\TypedData\SolrMultisiteFieldDefinition;

/**
 * Defines the "Solr multisite field" data type.
 *
 * Instances of this class wrap Search API Field objects and allow to deal with
 * fields based upon the Typed Data API.
 */
#[DataType(
  id: 'solr_multisite_field',
  label: new TranslatableMarkup('Solr multisite field'),
  description: new TranslatableMarkup('Fields from a multisite Solr document.'),
  definition_class: SolrMultisiteFieldDefinition::class,
)]
class SolrMultisiteField extends SolrField {


  /**
   * Field name.
   *
   * @var string
   */
  protected static $solrField = 'solr_multisite_field';

}
