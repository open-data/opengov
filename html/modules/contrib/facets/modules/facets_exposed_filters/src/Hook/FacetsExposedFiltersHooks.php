<?php

namespace Drupal\facets_exposed_filters\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets_exposed_filters\FacetsExposedFiltersHelper;
use Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\Query\QueryInterface;
use Drupal\views\Plugin\Block\ViewsExposedFilterBlock;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Hook implementations for exposed facet filters.
 */
final class FacetsExposedFiltersHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_data_alter().
   *
   * @param array<string, mixed> $data
   *   The Views data array.
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    foreach (Index::loadMultiple() as $index) {
      foreach ($index->getFields() as $field) {
        $fieldLabel = $field->getLabel() . ' (' . $field->getFieldIdentifier() . ')';

        $data['search_api_index_' . $index->id()]['facets_' . $field->getFieldIdentifier()] = [
          'title' => $fieldLabel,
          'help' => $this->t('Facets enabled filter. Available options will narrow down based on resultset.', ['@label' => $fieldLabel]),
          'filter' => [
            'id' => 'facets_filter',
            'search_api_field_label' => $field->getLabel(),
            'search_api_field_identifier' => $field->getFieldIdentifier(),
          ],
          'group' => $this->t('Facets'),
        ];
      }
    }
  }

  /**
   * Implements hook_search_api_query_alter().
   */
  #[Hook('search_api_query_alter')]
  public function searchApiQueryAlter(QueryInterface $query): void {
    $view = $query->getOption('search_api_view');
    if (!$view || (!$view->exposed_widgets && isset($view->display_handler) && !$view->display_handler->getOption('exposed_block'))) {
      return;
    }

    $backendId = NULL;
    $server = $query->getIndex()->getServerInstanceIfAvailable();
    if ($server) {
      $backendId = $server->getBackendId();
    }

    $querySearchApiFacetsOptions = [];
    foreach ($view->filter ?? [] as $filter) {
      if ($filter->getPluginId() !== 'facets_filter') {
        continue;
      }

      $configuration = $filter->getConfiguration();
      $fieldIdentifier = $configuration['search_api_field_identifier'];
      $facetOperator = $filter->options['facet']['query_operator'];

      // Solr applies self-filter exclusion only for OR facets. Force the
      // operator to OR for exposed range sliders so min/max bounds are
      // calculated from the broader facet distribution.
      if (
        $backendId === 'search_api_solr'
        && !empty($filter->options['facet']['processor_configs']['exposed_range_slider'])
      ) {
        $facetOperator = 'or';
      }

      $querySearchApiFacetsOptions[$fieldIdentifier] = [
        'field' => $fieldIdentifier,
        'limit' => $filter->options['facet']['hard_limit'] ?? 0,
        'operator' => $facetOperator,
        'min_count' => $filter->options['facet']['min_count'],
        'missing' => $filter->options['facet']['missing'] ?? FALSE,
        'missing_label' => $filter->options['facet']['missing_label'] ?? 'others',
        // @todo Investigate if this property (query_type) is used.
        'query_type' => 'search_api_string',
      ];
    }

    if ($querySearchApiFacetsOptions === []) {
      return;
    }

    $options = &$query->getOptions();
    $options['search_api_facets'] = $querySearchApiFacetsOptions;
  }

  /**
   * Implements hook_views_post_execute().
   */
  #[Hook('views_post_execute')]
  public function viewsPostExecute(ViewExecutable $view): void {
    if (!$view->exposed_widgets && !$view->display_handler->getOption('exposed_block')) {
      return;
    }

    $query = $view->getQuery();
    if (!$query instanceof SearchApiQuery) {
      return;
    }

    $searchApiResults = $query->getSearchApiResults();
    $searchApiFacets = $searchApiResults->getExtraData('search_api_facets');
    FacetsExposedFiltersHelper::markViewPostExecuted($view->id(), $view->current_display);

    if ($searchApiFacets) {
      foreach ($view->filter as $filter) {
        if (!$filter instanceof FacetsFilter) {
          continue;
        }

        $configuration = $filter->getConfiguration();
        $fieldIdentifier = $configuration['search_api_field_identifier'];
        if (isset($searchApiFacets[$fieldIdentifier])) {
          $filter->facet_results = $searchApiFacets[$fieldIdentifier];
          $filter->facet_slider_raw_results = $searchApiFacets[$fieldIdentifier];
        }
      }
    }

    $exposedForm = $view->display_handler->getPlugin('exposed_form');
    if (method_exists($exposedForm, 'renderExposedForm')) {
      $view->exposed_widgets = $exposedForm->renderExposedForm();
    }
  }

  /**
   * Implements hook_views_filters_summary_info_alter().
   *
   * @param array<string, mixed> $info
   *   The filter summary info.
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter plugin instance.
   */
  #[Hook('views_filters_summary_info_alter')]
  public function viewsFiltersSummaryInfoAlter(array &$info, FilterPluginBase $filter): void {
    if (!$filter instanceof FacetsFilter) {
      return;
    }

    $rangeSliderConfiguration = $this->getRangeSliderConfiguration($filter);
    if ($rangeSliderConfiguration !== NULL) {
      $rangeSummaryItem = $this->getRangeSliderSummaryItem($filter, $rangeSliderConfiguration);
      if ($rangeSummaryItem !== NULL) {
        $info['value'] = [$rangeSummaryItem];
      }

      return;
    }

    $values = [];
    foreach ($filter->value as $rawValue) {
      $facetSummaryItem = FacetsExposedFiltersHelper::getNestedSummaryValue($filter->facet_results, $rawValue);
      if ($facetSummaryItem !== FALSE) {
        $values[] = $facetSummaryItem;
      }
    }
    $info['value'] = $values;
  }

  /**
   * Returns the Better Exposed Filters range slider configuration.
   *
   * @param \Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter $filter
   *   The facet filter.
   *
   * @return array<string, mixed>|null
   *   The range slider configuration, or NULL when another widget is used.
   */
  private function getRangeSliderConfiguration(FacetsFilter $filter): ?array {
    $view = $filter->view;
    $displayHandler = $view->display_handler ?? NULL;

    if (!is_object($displayHandler) || !method_exists($displayHandler, 'getOption')) {
      return NULL;
    }

    $exposedForm = $displayHandler->getOption('exposed_form');
    $befFilterConfigurations = $exposedForm['options']['bef']['filter'] ?? NULL;

    if (!is_array($befFilterConfigurations)) {
      return NULL;
    }

    foreach ($this->getPossibleFilterConfigurationKeys($filter) as $filterId) {
      $configuration = $befFilterConfigurations[$filterId] ?? NULL;
      if (
        is_array($configuration)
        && ($configuration['plugin_id'] ?? NULL) === 'facets_exposed_range_slider'
      ) {
        return $configuration;
      }
    }

    return NULL;
  }

  /**
   * Returns possible BEF configuration keys for the filter handler.
   *
   * @param \Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter $filter
   *   The facet filter.
   *
   * @return string[]
   *   Candidate keys.
   */
  private function getPossibleFilterConfigurationKeys(FacetsFilter $filter): array {
    $keys = [
      $filter->options['id'] ?? NULL,
      $filter->options['field'] ?? NULL,
      $filter->field ?? NULL,
    ];

    return array_values(array_unique(array_filter($keys, 'is_string')));
  }

  /**
   * Builds a summary item for an active range slider value.
   *
   * @param \Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter $filter
   *   The facet filter.
   * @param array<string, mixed> $rangeSliderConfiguration
   *   The Better Exposed Filters widget configuration.
   *
   * @return array<string, mixed>|null
   *   The summary item, or NULL when no range is active.
   */
  private function getRangeSliderSummaryItem(FacetsFilter $filter, array $rangeSliderConfiguration): ?array {
    $filterIdentifier = $filter->options['expose']['identifier'] ?? NULL;

    if (!is_string($filterIdentifier) || $filterIdentifier === '') {
      return NULL;
    }

    $exposedInput = $filter->view->getExposedInput();
    $rangeInput = is_array($exposedInput)
      ? ($exposedInput[$filterIdentifier] ?? NULL)
      : NULL;

    if (!is_array($rangeInput)) {
      return NULL;
    }

    $min = $this->normalizeRangeValue($rangeInput['min'] ?? NULL);
    $max = $this->normalizeRangeValue($rangeInput['max'] ?? NULL);

    if ($min === NULL && $max === NULL) {
      return NULL;
    }

    $valuePrefix = $this->normalizeRangeAffix($rangeSliderConfiguration['tooltips_value_prefix'] ?? '');
    $valueSuffix = $this->normalizeRangeAffix($rangeSliderConfiguration['tooltips_value_suffix'] ?? '');

    if ($min !== NULL && $max !== NULL) {
      $value = $this->formatRangeValue($min, $valuePrefix, $valueSuffix)
        . ' - '
        . $this->formatRangeValue($max, $valuePrefix, $valueSuffix);
    }
    elseif ($min !== NULL) {
      $value = (string) $this->t('From @value', [
        '@value' => $this->formatRangeValue($min, $valuePrefix, $valueSuffix),
      ]);
    }
    else {
      $value = (string) $this->t('Up to @value', [
        '@value' => $this->formatRangeValue($max, $valuePrefix, $valueSuffix),
      ]);
    }

    return [
      'id' => 0,
      'raw' => 'min|max',
      'value' => $value,
    ];
  }

  /**
   * Normalizes an exposed range value.
   *
   * @param mixed $value
   *   The exposed value.
   *
   * @return string|null
   *   The trimmed value, or NULL when it is empty or unsupported.
   */
  private function normalizeRangeValue(mixed $value): ?string {
    if (!is_scalar($value)) {
      return NULL;
    }

    $value = trim((string) $value);

    return $value === '' ? NULL : $value;
  }

  /**
   * Normalizes a configured range value prefix or suffix.
   *
   * @param mixed $affix
   *   The configured affix.
   *
   * @return string
   *   The affix value.
   */
  private function normalizeRangeAffix(mixed $affix): string {
    return is_scalar($affix) ? (string) $affix : '';
  }

  /**
   * Formats one range endpoint for the summary.
   *
   * @param string $value
   *   The exposed range value.
   * @param string $prefix
   *   The configured value prefix.
   * @param string $suffix
   *   The configured value suffix.
   *
   * @return string
   *   The formatted range endpoint.
   */
  private function formatRangeValue(string $value, string $prefix, string $suffix): string {
    return $prefix . $value . $suffix;
  }

  /**
   * Implements hook_block_build_alter().
   *
   * @param array<string, mixed> $build
   *   The block render array.
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin being built.
   */
  #[Hook('block_build_alter')]
  public function blockBuildAlter(array &$build, BlockPluginInterface $block): void {
    if (!$block instanceof ViewsExposedFilterBlock) {
      return;
    }

    $view = $block->getViewExecutable();
    $view->initHandlers();
    foreach ($view->filter as $filter) {
      if (!$filter instanceof FacetsFilter) {
        continue;
      }

      $filterId = $filter->options['id'];
      $display = $view->current_display;
      $processedFacet = FacetsExposedFiltersHelper::getProcessedFacet($view->id(), $display, $filterId);
      if (!$processedFacet) {
        $view->execute($display);
      }
      break;
    }
  }

}
