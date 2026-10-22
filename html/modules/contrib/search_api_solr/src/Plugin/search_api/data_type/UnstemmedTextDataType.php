<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;
use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a not stemmed full text data type.
 */
#[SearchApiDataType(
  id: 'solr_text_unstemmed',
  label: new TranslatableMarkup('Fulltext Unstemmed'),
  description: new TranslatableMarkup('Full text field without stemming.'),
  fallback_type: 'text',
)]
class UnstemmedTextDataType extends TextDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'tu';
  }

}
