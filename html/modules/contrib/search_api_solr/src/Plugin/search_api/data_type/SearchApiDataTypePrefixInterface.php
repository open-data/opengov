<?php

declare(strict_types=1);

namespace Drupal\search_api_solr\Plugin\search_api\data_type;

/**
 * Use for prefixes Search API data types.
 */
interface SearchApiDataTypePrefixInterface {

  /**
   * Returns the plugin prefix.
   *
   * @return string
   *   The prefix.
   */
  public static function getPrefix(): string;

}
