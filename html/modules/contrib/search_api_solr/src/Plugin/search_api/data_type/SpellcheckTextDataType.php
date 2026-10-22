<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;

/**
 * Provides data type to feed the suggester component.
 */
#[SearchApiDataType(
  id: 'solr_text_spellcheck',
  label: new TranslatableMarkup('Spellcheck'),
  description: new TranslatableMarkup('Full text field to feed the spellcheck component.'),
  fallback_type: 'text',
)]
class SpellcheckTextDataType extends WhiteSpaceTokensTextDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'spellcheck';
  }

}
