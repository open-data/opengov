<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\search_api\Query\QueryInterface;

/**
 * Provides support for string facets within the Search API scope.
 *
 * This is the default implementation that works with all backends and data
 * types. While you could use this query type for every data type, other query
 * types will usually be better suited for their specific data type.
 *
 * For example, the SearchApiDate query type will handle its input as a DateTime
 * value, while this class would only be able to work with it as a string.
 *
 * @FacetsQueryType(
 *   id = "search_api_string",
 *   label = @Translation("String"),
 * )
 */
class SearchApiString extends QueryTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;
    $operator = $this->facet->getQueryOperator();
    $field_identifier = $this->facet->getFieldIdentifier();
    $exclude = $this->facet->getExclude();

    if ($query->getProcessingLevel() === QueryInterface::PROCESSING_FULL) {
      // Set the options for the actual query.
      $options = &$query->getOptions();
      $options['search_api_facets'][$field_identifier] = $this->getFacetOptions();
    }

    // Add the filter to the query if there are active values.
    $active_items = $this->facet->getActiveItems();

    if (count($active_items)) {
      $filter = $query->createConditionGroup($operator, ['facet:' . $field_identifier]);
      foreach ($active_items as $value) {
        $this->addActiveItemCondition($query, $filter, $value, $exclude);
      }
      $query->addConditionGroup($filter);
    }
  }

  /**
   * Adds an active facet item to the query condition group.
   *
   * Missing buckets must exclude every non-missing value at once, so they are
   * always represented as nested AND groups even when the facet uses OR logic.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query being built.
   * @param \Drupal\search_api\Query\ConditionGroupInterface $filter
   *   The outer facet condition group.
   * @param string $value
   *   The active facet value.
   * @param bool $exclude
   *   Whether the facet uses exclusion mode.
   */
  protected function addActiveItemCondition(QueryInterface $query, $filter, string $value, bool $exclude): void {
    if (!str_starts_with($value, '!(')) {
      $filter->addCondition($this->facet->getFieldIdentifier(), $value, $exclude ? '<>' : '=');
      return;
    }

    $delimiter = ',';
    $url_processor_handler = $this->facet->getProcessors()['url_processor_handler'] ?? NULL;

    if ($url_processor_handler) {
      /** @var \Drupal\facets\UrlProcessor\UrlProcessorInterface $urlProcessor */
      $urlProcessor = $url_processor_handler->getProcessor();
      $delimiter = $urlProcessor->getDelimiter();
    }

    $missing_values = explode($delimiter, substr($value, 2, -1));
    $missing_filter = $query->createConditionGroup('AND', ['facet:' . $this->facet->getFieldIdentifier()]);

    foreach ($missing_values as $missing_value) {
      // Note that $exclude needs to be inverted for "missing".
      $missing_filter->addCondition($this->facet->getFieldIdentifier(), $missing_value, !$exclude ? '<>' : '=');
    }

    $filter->addConditionGroup($missing_filter);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();
    $unprocessed_active_items = $this->facet->getActiveItems();

    if (!empty($this->results)) {
      $facet_results = [];
      foreach ($this->results as $result) {
        if ($result['count'] || $query_operator === 'or') {
          $result_filter = $result['filter'] ?? '';
          if ($result_filter[0] === '"') {
            $result_filter = substr($result_filter, 1);
          }
          if ($result_filter[strlen($result_filter) - 1] === '"') {
            $result_filter = substr($result_filter, 0, -1);
          }
          if (($key = array_search($result_filter, $unprocessed_active_items)) !== FALSE) {
            unset($unprocessed_active_items[$key]);
          }
          elseif ($result_filter === '!') {
            foreach ($unprocessed_active_items as $key => $active_item) {
              if (str_starts_with((string) $active_item, '!(')) {
                unset($unprocessed_active_items[$key]);
              }
            }
          }
          $count = $result['count'];
          $result = new Result($this->facet, $result_filter, $result_filter, $count);
          $result->setMissing($this->facet->isMissing() && $result_filter === '!');
          $facet_results[$result_filter] = $result;
        }
      }

      // Add unprocessed active values to the result. These are selected items
      // that do not match the results anymore.
      foreach ($unprocessed_active_items as $val) {
        $result = new Result($this->facet, $val, $val, 0);
        $result->setActiveState(TRUE);
        $facet_results[] = $result;
      }

      if (isset($facet_results['!']) && $facet_results['!']->isMissing()) {
        $facet_results['!']->setMissingFilters(array_keys($facet_results));
      }

      $this->facet->setResults(array_values($facet_results));
    }

    return $this->facet;
  }

}
