<?php

namespace Drupal\facets_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\better_exposed_filters\Attribute\FiltersWidget;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\Sliders;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter;

/**
 * Range slider widget for exposed facet filters.
 */
#[FiltersWidget(
  id: 'facets_exposed_range_slider',
  title: new TranslatableMarkup('Exposed facet range slider'),
)]
class ExposedRangeSlider extends Sliders {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'snap_to_values' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $filter = NULL, array $filter_options = []): bool {
    return $filter instanceof FacetsFilter
      && !empty($filter->options['facet']['processor_configs']['exposed_range_slider']);
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $filter = $this->handler;

    if (!$filter instanceof FacetsFilter) {
      return;
    }

    $field_id = $filter->options['expose']['identifier'];

    if (empty($form[$field_id])) {
      return;
    }

    [$min_result, $max_result] = $this->getBoundaryResults($filter->facet_results);

    if (!$min_result instanceof ResultInterface || !$max_result instanceof ResultInterface) {
      return;
    }

    $min_value = (string) $min_result->getRawValue();
    $max_value = (string) $max_result->getRawValue();
    $preserve_active_range = $this->shouldPreserveActiveRange($min_result, $max_result);
    $processor_settings = $filter->options['facet']['processor_configs']['exposed_range_slider']['settings'] ?? [];
    $snap_results = $filter->facet_slider_raw_results ?: $filter->facet_results;
    $snap_values = $this->getNumericResultValues($snap_results);
    $snap_to_values = !empty($this->configuration['snap_to_values'])
      && \count($snap_values) >= 2;
    $exposed_input = $this->view->getExposedInput()[$field_id] ?? [];
    $active_min = \is_array($exposed_input) ? (string) ($exposed_input['min'] ?? '') : '';
    $active_max = \is_array($exposed_input) ? (string) ($exposed_input['max'] ?? '') : '';

    $has_active_range = trim($active_min) !== '' || trim($active_max) !== '';

    if (
      $has_active_range
      && !$preserve_active_range
      && is_numeric($min_value)
      && is_numeric($max_value)
      && $this->hasOutOfBoundsActiveRange(
        $active_min,
        $active_max,
        (float) $min_value,
        (float) $max_value,
      )
    ) {
      $this->discardActiveRangeSelection($field_id);
      $form[$field_id]['#access'] = FALSE;

      return;
    }

    $single_value_range = is_numeric($min_value)
      && is_numeric($max_value)
      && (float) $min_value === (float) $max_value;
    $render_active_single_value = $single_value_range && $has_active_range;

    // Hide single-value facets only when they are not currently active.
    if ($single_value_range && !$has_active_range) {
      $form[$field_id]['#access'] = FALSE;

      return;
    }

    $title = $form[$field_id]['#title'] ?? $filter->options['expose']['label'] ?? '';
    $attached = $form[$field_id]['#attached'] ?? [];
    $attributes = $form[$field_id]['#attributes'] ?? [];
    $existing_classes = [];

    if (isset($attributes['class']) && \is_array($attributes['class'])) {
      $existing_classes = $attributes['class'];
    }
    $attributes['class'] = array_values(array_unique(array_merge(
      $existing_classes,
      [
        'facets-exposed-range-slider',
        'fieldgroup',
        'form-composite',
      ],
    )));

    if ($render_active_single_value) {
      $attributes['class'][] = 'facets-exposed-range-slider--active-single';
    }
    $data_selector = Html::getId($field_id);
    $selected_value = trim($active_max) !== ''
      ? $active_max
      : (trim($active_min) !== '' ? $active_min : $max_value);
    $min_element = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum'),
      '#size' => 10,
      '#weight' => -1,
      '#default_value' => $active_min,
      '#attributes' => [
        'class' => ['facets-exposed-range-slider__min'],
        'data-facets-exposed-range-slider-min' => TRUE,
      ],
    ];
    $max_element = [
      '#type' => 'textfield',
      '#title' => $render_active_single_value ? $this->t('Selected value') : $this->t('Maximum'),
      '#size' => 10,
      '#weight' => 1,
      '#default_value' => $render_active_single_value ? $selected_value : $active_max,
      '#attributes' => [
        'class' => ['facets-exposed-range-slider__max'],
        'data-facets-exposed-range-slider-max' => TRUE,
      ],
    ];

    if ($render_active_single_value) {
      $max_element['#attributes']['readonly'] = 'readonly';
      $form[$field_id] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $title,
        '#attributes' => $attributes,
        'min' => $min_element,
        'max' => $max_element,
        '#attached' => $attached,
      ];
    }
    else {
      $slider_element = [
        '#type' => 'container',
        '#weight' => 0,
        '#attributes' => [
          'class' => ['facets-exposed-range-slider__slider'],
          'data-facets-exposed-range-slider' => $data_selector,
          'data-min' => $min_value,
          'data-max' => $max_value,
          'data-step' => $processor_settings['step'] ?? 1,
          'data-animate' => ($this->configuration['animate'] === self::ANIMATE_CUSTOM) ? $this->configuration['animate_ms'] : $this->configuration['animate'],
          'data-orientation' => $this->configuration['orientation'],
          'data-tooltips' => $this->configuration['enable_tooltips'] ? '1' : '0',
          'data-tooltips-value-prefix' => $this->configuration['tooltips_value_prefix'],
          'data-tooltips-value-suffix' => $this->configuration['tooltips_value_suffix'],
          'data-snap-to-values' => $snap_to_values ? '1' : '0',
          'data-snap-values' => $snap_to_values ? implode(',', $snap_values) : '',
          'data-preserve-active-range' => $preserve_active_range ? '1' : '0',
        ],
      ];
      $form[$field_id] = [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $title,
        '#attributes' => $attributes,
        'min' => $min_element,
        'slider' => $slider_element,
        'max' => $max_element,
        '#attached' => $attached,
      ];
      $form[$field_id]['#attached']['library'][] = 'facets_exposed_filters/exposed_range_slider';
      $form[$field_id]['#attached']['drupalSettings']['facets_exposed_filters']['range_sliders'][$data_selector] = [
        'min' => $min_value,
        'max' => $max_value,
        'step' => $processor_settings['step'] ?? 1,
        'animate' => ($this->configuration['animate'] === self::ANIMATE_CUSTOM) ? $this->configuration['animate_ms'] : $this->configuration['animate'],
        'orientation' => $this->configuration['orientation'],
        'tooltips' => $this->configuration['enable_tooltips'],
        'tooltips_value_prefix' => $this->configuration['tooltips_value_prefix'],
        'tooltips_value_suffix' => $this->configuration['tooltips_value_suffix'],
        'snap_to_values' => $snap_to_values,
        'snap_values' => $snap_values,
        'preserve_active_range' => $preserve_active_range,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    foreach (['min', 'max', 'step'] as $field_id) {
      $form[$field_id]['#access'] = FALSE;
    }

    $form['snap_to_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Snap to available facet values'),
      '#default_value' => !empty($this->configuration['snap_to_values']),
      '#description' => $this->t('Limit slider handles to values returned by facet results instead of a continuous step range.'),
    ];

    return $form;
  }

  /**
   * Gets the numeric minimum and maximum result independent of result order.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The facet results.
   *
   * @return array<int, \Drupal\facets\Result\ResultInterface|null>
   *   The minimum and maximum result.
   */
  private function getBoundaryResults(array $results): array {
    $results = array_filter($results, static function ($result): bool {
      return $result instanceof ResultInterface && is_numeric($result->getRawValue());
    });

    if ($results === []) {
      return [NULL, NULL];
    }

    usort($results, static function (ResultInterface $a, ResultInterface $b): int {
      return (float) $a->getRawValue() <=> (float) $b->getRawValue();
    });

    return [reset($results), end($results)];
  }

  /**
   * Determines whether the slider must preserve its active range values.
   *
   * @param \Drupal\facets\Result\ResultInterface $min_result
   *   The minimum result.
   * @param \Drupal\facets\Result\ResultInterface $max_result
   *   The maximum result.
   *
   * @return bool
   *   TRUE when both boundary results represent synthetic active values.
   */
  private function shouldPreserveActiveRange(ResultInterface $min_result, ResultInterface $max_result): bool {
    return (bool) $min_result->get('facets_exposed_filters_preserve_active_range')
      && (bool) $max_result->get('facets_exposed_filters_preserve_active_range');
  }

  /**
   * Returns sorted unique numeric raw values from facet results.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The facet results.
   *
   * @return array<int, float>
   *   The numeric raw values.
   */
  private function getNumericResultValues(array $results): array {
    $numeric_values = [];

    foreach ($results as $result) {
      $numeric_value = $this->extractNumericResultValue($result);

      if ($numeric_value === NULL) {
        continue;
      }

      $numeric_values[] = $numeric_value;
    }

    if ($numeric_values === []) {
      return [];
    }

    sort($numeric_values, \SORT_NUMERIC);

    $unique_values = [];

    foreach ($numeric_values as $numeric_value) {
      if (
        $unique_values === []
        || abs($numeric_value - end($unique_values)) > \PHP_FLOAT_EPSILON
      ) {
        $unique_values[] = $numeric_value;
      }
    }

    return $unique_values;
  }

  /**
   * Extracts one numeric raw value from facet result structures.
   *
   * Supports processed Facets result objects as well as raw Search API facet
   * arrays (`['filter' => ..., 'count' => ...]`) used before query-type build.
   *
   * @param mixed $result
   *   A facet result item.
   *
   * @return float|null
   *   The numeric value, or NULL when no numeric value can be extracted.
   */
  private function extractNumericResultValue(mixed $result): ?float {
    $raw_value = NULL;

    if ($result instanceof ResultInterface) {
      $raw_value = $result->getRawValue();
    }
    elseif (\is_array($result) && \array_key_exists('filter', $result)) {
      $raw_value = $result['filter'];

      while (\is_array($raw_value) && $raw_value !== []) {
        $raw_value = reset($raw_value);
      }
    }
    elseif (\is_scalar($result)) {
      $raw_value = $result;
    }

    if (!\is_scalar($raw_value)) {
      return NULL;
    }

    $normalized = trim((string) $raw_value, " \t\n\r\0\x0B\"'");

    if ($normalized === '' || strcasecmp($normalized, 'NULL') === 0 || !is_numeric($normalized)) {
      return NULL;
    }

    return (float) $normalized;
  }

  /**
   * Checks whether the submitted range is invalid for current boundaries.
   *
   * @param string $active_min
   *   The submitted minimum.
   * @param string $active_max
   *   The submitted maximum.
   * @param float $min_value
   *   The available minimum boundary.
   * @param float $max_value
   *   The available maximum boundary.
   *
   * @return bool
   *   TRUE when the active range should be treated as invalid.
   */
  private function hasOutOfBoundsActiveRange(
    string $active_min,
    string $active_max,
    float $min_value,
    float $max_value,
  ): bool {
    $active_min = trim($active_min);
    $active_max = trim($active_max);

    if ($active_min === '' && $active_max === '') {
      return FALSE;
    }

    if (
      $active_min === ''
      || $active_max === ''
      || !is_numeric($active_min)
      || !is_numeric($active_max)
    ) {
      return TRUE;
    }

    $submitted_min = (float) $active_min;
    $submitted_max = (float) $active_max;

    if ($submitted_min > $submitted_max) {
      [$submitted_min, $submitted_max] = [$submitted_max, $submitted_min];
    }

    $tolerance = \PHP_FLOAT_EPSILON * 10;

    // Treat only disjoint ranges as invalid. Overlapping ranges can happen
    // naturally when additional filters narrow current facet boundaries.
    return $submitted_max < ($min_value - $tolerance)
      || $submitted_min > ($max_value + $tolerance);
  }

  /**
   * Removes an invalid range selection from current exposed state.
   *
   * @param string $field_id
   *   The exposed filter identifier.
   */
  private function discardActiveRangeSelection(string $field_id): void {
    $exposed_input = $this->view->getExposedInput();
    $exposed_input = \is_array($exposed_input) ? $exposed_input : [];
    unset($exposed_input[$field_id]);
    $this->view->setExposedInput($exposed_input);

    if (isset($this->view->exposed_raw_input) && \is_array($this->view->exposed_raw_input)) {
      unset($this->view->exposed_raw_input[$field_id]);
    }

    $request = $this->request;

    if (!$request) {
      return;
    }

    $request->query->remove($field_id);
    $request->request->remove($field_id);
    $request->overrideGlobals();
  }

}
