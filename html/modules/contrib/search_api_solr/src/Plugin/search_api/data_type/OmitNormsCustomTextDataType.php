<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;
use Drupal\search_api\Plugin\search_api\data_type\TextDataType;
use Drupal\search_api_solr\Plugin\Derivative\OmitNormsCustomTextDataType as OmitNormsCustomTextDataTypeDeriver;

/**
 * Provides a not stemmed full text data type which omits norms.
 */
#[SearchApiDataType(
  id: 'solr_text_custom_omit_norms',
  label: new TranslatableMarkup('Fulltext Custom Omit norms'),
  description: new TranslatableMarkup('Custom full text field which omits norms.'),
  fallback_type: 'text',
  deriver: OmitNormsCustomTextDataTypeDeriver::class
)]
class OmitNormsCustomTextDataType extends TextDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'toc';
  }

}
