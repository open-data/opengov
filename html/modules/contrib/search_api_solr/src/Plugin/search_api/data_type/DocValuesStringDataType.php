<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;
use Drupal\search_api\Plugin\search_api\data_type\StringDataType;

/**
 * Provides a storage-only string data type.
 */
#[SearchApiDataType(
  id: 'solr_string_docvalues',
  label: new TranslatableMarkup('docValues-only'),
  description: new TranslatableMarkup("A docValues-only field. You can store any string and retrieve it from the index but you can't search through it. In opposite to storage-only, docValues will be stored, so the field is compatible to the export handler (and probably facets)."),
  fallback_type: 'string',
)]
class DocValuesStringDataType extends StringDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'zdv';
  }

}
