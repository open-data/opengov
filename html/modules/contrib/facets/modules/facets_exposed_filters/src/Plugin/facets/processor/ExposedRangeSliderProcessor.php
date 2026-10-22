<?php

namespace Drupal\facets_exposed_filters\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Exception\InvalidQueryTypeException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PostQueryProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\Result;
use Drupal\facets\Result\ResultInterface;

/**
 * Provides range data for exposed facet sliders.
 *
 * @FacetsProcessor(
 *     id="exposed_range_slider",
 *     label=@Translation("Exposed range slider"),
 *     description=@Translation("Render an exposed numeric facet as a range slider."),
 *     stages={
 *         "pre_query": 60,
 *         "post_query": 60
 *     }
 * )
 */
class ExposedRangeSliderProcessor extends ProcessorPluginBase implements PreQueryProcessorInterface, PostQueryProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet): bool {
    if (method_exists($facet, 'getFacetType') && $facet->getFacetType() !== 'facets_exposed_filter') {
      return FALSE;
    }

    $facet_source = $facet->getFacetSource();

    if (!$facet_source) {
      return FALSE;
    }

    try {
      return isset($facet_source->getQueryTypesForFacet($facet)['range']);
    }
    catch (InvalidQueryTypeException) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet): void {
    $active_items = $facet->getActiveItems();

    if (!$this->hasRangeKeys($active_items)) {
      return;
    }

    $range = $this->sanitizeRange($active_items);

    if ($range === []) {
      $facet->setActiveItems([]);

      return;
    }

    $facet->setActiveItems($range);
  }

  /**
   * {@inheritdoc}
   */
  public function postQuery(FacetInterface $facet): void {
    $results = array_values(array_filter(
      $facet->getResults(),
      static function ($result): bool {
        return $result instanceof ResultInterface && is_numeric($result->getRawValue());
      },
    ));

    if ($results === []) {
      $active_range = $this->getActiveRange($facet->getActiveItems());

      if ($active_range !== []) {
        $this->setRangeResults($facet, $active_range[0], $active_range[1], 0, 0, TRUE);
      }

      return;
    }

    usort($results, static function (ResultInterface $a, ResultInterface $b): int {
      return (float) $a->getRawValue() <=> (float) $b->getRawValue();
    });

    $step = (string) ($this->configuration['step'] ?? 1);
    $precision = $this->getStepPrecision($step);

    $min_result = reset($results);
    $max_result = end($results);

    if (!$min_result instanceof ResultInterface || !$max_result instanceof ResultInterface) {
      return;
    }

    $min_value = number_format($this->floorPlus((float) $min_result->getRawValue(), $precision), $precision, '.', '');
    $max_value = number_format($this->ceilPlus((float) $max_result->getRawValue(), $precision), $precision, '.', '');

    $this->setRangeResults($facet, $min_value, $max_value, $min_result->getCount(), $max_result->getCount());
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    return [
      'step' => [
        '#type' => 'number',
        '#step' => 0.001,
        '#title' => $this->t('Slider step'),
        '#default_value' => $config['step'],
        '#min' => 0.001,
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'step' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'range';
  }

  /**
   * Checks whether active items are in exposed range shape.
   *
   * @param array<mixed> $active_items
   *   The active items.
   *
   * @return bool
   *   TRUE when the active values contain min/max keys.
   */
  private function hasRangeKeys(array $active_items): bool {
    return \array_key_exists('min', $active_items) || \array_key_exists('max', $active_items);
  }

  /**
   * Sanitizes an exposed min/max range.
   *
   * @param array<mixed> $active_items
   *   The active items.
   *
   * @return array<int, array<int, string>>
   *   A single range tuple matching Facets' normal range-slider format, or an
   *   empty array when it is not usable.
   */
  private function sanitizeRange(array $active_items): array {
    $min = $active_items['min'] ?? '';
    $max = $active_items['max'] ?? '';
    $min = \is_scalar($min) ? trim((string) $min) : '';
    $max = \is_scalar($max) ? trim((string) $max) : '';

    if ($min === '' || $max === '') {
      return [];
    }

    if (!is_numeric($min) || !is_numeric($max)) {
      return [];
    }

    if ((float) $min > (float) $max) {
      [$min, $max] = [$max, $min];
    }

    return [[$min, $max]];
  }

  /**
   * Gets the active range tuple from normalized or exposed range values.
   *
   * @param array<mixed> $active_items
   *   The active items.
   *
   * @return array<int, string>
   *   The active min/max tuple, or an empty array when none is available.
   */
  private function getActiveRange(array $active_items): array {
    $range = $this->sanitizeRange($active_items);

    if ($range !== []) {
      return reset($range);
    }

    if (count($active_items) !== 1) {
      return [];
    }

    $range = reset($active_items);

    if (!is_array($range) || count($range) < 2) {
      return [];
    }

    $min = $range[0] ?? '';
    $max = $range[1] ?? '';

    if (!is_scalar($min) || !is_scalar($max)) {
      return [];
    }

    $min = trim((string) $min);
    $max = trim((string) $max);

    if ($min === '' || $max === '' || !is_numeric($min) || !is_numeric($max)) {
      return [];
    }

    if ((float) $min > (float) $max) {
      [$min, $max] = [$max, $min];
    }

    return [$min, $max];
  }

  /**
   * Sets the synthetic range boundary results used by the exposed slider.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being processed.
   * @param string $min_value
   *   The minimum value.
   * @param string $max_value
   *   The maximum value.
   * @param int $min_count
   *   The minimum result count.
   * @param int $max_count
   *   The maximum result count.
   * @param bool $preserve_active_range
   *   TRUE when the range values are synthetic active values, not real result
   *   boundaries.
   */
  private function setRangeResults(FacetInterface $facet, string $min_value, string $max_value, int $min_count, int $max_count, bool $preserve_active_range = FALSE): void {
    $min_result = new Result($facet, $min_value, $min_value, $min_count);
    $max_result = new Result($facet, $max_value, $max_value, $max_count);

    if ($preserve_active_range) {
      $min_result->set('facets_exposed_filters_preserve_active_range', TRUE);
      $max_result->set('facets_exposed_filters_preserve_active_range', TRUE);
    }

    $facet->setResults([
      $min_result,
      $max_result,
    ]);
  }

  /**
   * Gets the decimal precision implied by the configured step.
   *
   * @param string $step
   *   The step value.
   *
   * @return int
   *   The number of decimal places.
   */
  private function getStepPrecision(string $step): int {
    if (!str_contains($step, '.')) {
      return 0;
    }

    return \strlen(rtrim(substr($step, strpos($step, '.') + 1), '0'));
  }

  /**
   * Rounds a value up while preserving decimal precision.
   *
   * @param float $value
   *   The number to round.
   * @param int $precision
   *   The decimal precision.
   *
   * @return float
   *   The rounded value.
   */
  private function ceilPlus(float $value, int $precision): float {
    if ($precision === 0) {
      return (float) ceil($value);
    }

    $regulator = $value + 0.5 / (10 ** $precision);

    return round($regulator, $precision, $regulator > 0 ? \PHP_ROUND_HALF_DOWN : \PHP_ROUND_HALF_UP);
  }

  /**
   * Rounds a value down while preserving decimal precision.
   *
   * @param float $value
   *   The number to round.
   * @param int $precision
   *   The decimal precision.
   *
   * @return float
   *   The rounded value.
   */
  private function floorPlus(float $value, int $precision): float {
    if ($precision === 0) {
      return (float) floor($value);
    }

    $regulator = $value - 0.5 / (10 ** $precision);

    return round($regulator, $precision, $regulator > 0 ? \PHP_ROUND_HALF_UP : \PHP_ROUND_HALF_DOWN);
  }

}
