<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;
use Drupal\search_api\Plugin\search_api\data_type\TextDataType;
use Drupal\search_api_solr\Plugin\Derivative\CustomTextDataType as CustomTextDataTypeDeriver;

/**
 * Provides a custom full text data type.
 */
#[SearchApiDataType(
  id: 'solr_text_custom',
  label: new TranslatableMarkup('Fulltext Custom'),
  description: new TranslatableMarkup('Custom full text field.'),
  fallback_type: 'text',
  deriver: CustomTextDataTypeDeriver::class,
)]
class CustomTextDataType extends TextDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'tc';
  }

}
