<?php

namespace Drupal\search_api_solr\Plugin\search_api\parse_mode;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiParseMode;
use Drupal\search_api\Plugin\search_api\parse_mode\Phrase;

/**
 * Represents a parse mode.
 */
#[SearchApiParseMode(
  id: 'sloppy_phrase',
  label: new TranslatableMarkup('Phrase search with sloppiness'),
  description: new TranslatableMarkup('The query is interpreted as a single phrase. Solr will also show results where the words are not directly positioned next to each other. The scoring will be lower the further away the words are from each other'),
)]
class SloppyPhrase extends Phrase {

}
