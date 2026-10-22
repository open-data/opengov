<?php

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiDataType;
use Drupal\search_api\Plugin\search_api\data_type\StringDataType;

/**
 * Provides a storage-only string data type.
 */
#[SearchApiDataType(
  id: 'solr_string_storage',
  label: new TranslatableMarkup('Storage-only'),
  description: new TranslatableMarkup("A storage-only field. You can store any string and retrieve it from the index but you can't search through it."),
  fallback_type: 'string',
)]
class StorageStringDataType extends StringDataType implements SearchApiDataTypePrefixInterface {

  /**
   * {@inheritdoc}
   */
  public static function getPrefix(): string {
    return 'z';
  }

}
