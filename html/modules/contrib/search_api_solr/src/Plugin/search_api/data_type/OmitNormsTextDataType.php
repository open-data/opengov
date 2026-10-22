<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;
use Drupal\search_api\Plugin\search_api\data_type\TextDataType;

/**
 * Provides a full text data type which omit norms.
 */
#[SearchApiDataType(
  id: 'solr_text_omit_norms',
  label: new TranslatableMarkup('Fulltext Omit norms'),
  description: new TranslatableMarkup('Full text field which omits norms.'),
  fallback_type: 'text',
)]
class OmitNormsTextDataType extends TextDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'to';
  }

}
