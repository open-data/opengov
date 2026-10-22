<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;

/**
 * Provides data type to feed the suggester component.
 */
#[SearchApiDataType(
  id: 'solr_text_suggester',
  label: new TranslatableMarkup('Suggester'),
  description: new TranslatableMarkup('Full text field to feed the suggester component.'),
  fallback_type: 'text',
)]
class SuggesterTextDataType extends WhiteSpaceTokensTextDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'tw';
  }

}
