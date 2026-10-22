<?php

namespace Drupal\search_api_solr;

use Drupal\search_api_solr\Solarium\Autocomplete\Result as AutocompleteResult;
use Solarium\Component\ComponentAwareQueryInterface;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Result\Result as SelectResult;

/**
 * Provides spellcheck related methods used by search backends and plugins.
 */
trait SolrSpellcheckBackendTrait {

  /**
   * Get the spellcheck suggestions from the given query result.
   *
   * @param \Solarium\Core\Query\Result\ResultInterface $result
   *   A query result.
   *
   * @return array
   *   An array of suggestions.
   */
  protected function extractSpellCheckSuggestions(ResultInterface $result) {
    $suggestions = [];
    if (
      ($result instanceof AutocompleteResult || $result instanceof SelectResult)
      && ($spellcheck_results = $result->getComponent(ComponentAwareQueryInterface::COMPONENT_SPELLCHECK))
    ) {
      /** @var \Solarium\Component\Result\Spellcheck\Suggestion $correction */
      foreach ($spellcheck_results as $correction) {
        $keys = [];
        foreach ($correction->getWords() as $word) {
          $keys[] = $word['word'];
        }
        if ($keys) {
          $suggestions[$correction->getOriginalTerm()] = $keys;
        }
      }
    }
    return $suggestions;
  }

}
