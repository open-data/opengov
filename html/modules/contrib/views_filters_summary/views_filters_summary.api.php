<?php

/**
 * @file
 * Describes hooks provided by the Views Filters Summary module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter a filter's summary info definition.
 *
 * Allows developers to adjust how a given Views exposed
 * filter summary is displayed.
 *
 * @param array $info
 *   An array of definition properties.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @see \Drupal\views_filters_summary\Plugin\views\area\ViewsFiltersSummary::buildFilterDefinition()
 */
function hook_views_filters_summary_info_alter(array &$info, \Drupal\views\Plugin\views\filter\FilterPluginBase $filter) {
  if ($filter->getPluginId() === 'datetime' && $time = strtotime($filter->value['value'])) {
    $info['value'] = [
      [
        'id' => 0,
        'raw' => $filter->value['value'],
        'value' => \Drupal::service('date.formatter')->format(
          $time,
          'custom',
          'F d, Y'
        ),
      ],
    ];
  }
}

/**
 * Alter a filter's summary replacement items.
 *
 * Allows developers to add new replacement items to be displayed in the
 * summary text.
 *
 * @param array $replacements
 *   An array of replacement items.
 * @param \Drupal\views\ViewExecutable $view
 *   The view instance.
 *
 * @see \Drupal\views_filters_summary\Plugin\views\area\ViewsFiltersSummary::defineReplacements()
 */
function hook_views_filters_summary_replacements_alter(&$replacements, $view) {
  foreach ($view->display_handler->handlers["filter"] as $filter) {
    if ($filter->getPluginId() == 'search_api_fulltext') {
      $identifier = $filter->options["expose"]["identifier"];
      $replacements['search_api_fulltext'] =
        \Drupal\Component\Utility\Html::escape($view->exposed_data[$identifier]);
    }
  }
}

/**
 * Alter a filter's summary item.
 *
 * Allows developers to modify a specific item to be displayed in the
 * summary text.
 *
 * @param array $item
 *   A filter item.
 *
 * @see \Drupal\views_filters_summary\Plugin\views\area\ViewsFiltersSummary::buildFilterSummaryItem()
 */
function hook_views_filters_summary_item_alter(&$item) {
  // Example taken from the views_filter_summary_a11y submodule.
  $value = $item['value'];
  $aria_label = $item['link']['#attributes']['aria-label'];
  $item['link']['#title'] = [
    '#markup' =>
    '<span aria-hidden="true" class="remove-label">' . $value . '</span>' .
    '<span class="visually-hidden">' . $aria_label . '</span>' .
    '<span aria-hidden="true" class="remove-button">X</span>',
  ];
  $item['link']['#options'] = [
    'html' => TRUE,
  ];
}

/**
 * Allow filter plugins to specify an alias to use for processing.
 *
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @return string|void
 *   The plugin alias to use to process the filter values.
 */
function hook_views_filters_summary_plugin_alias($filter) {
  if ($filter->getPluginId() === 'administrative_area') {
    // The administrative_area plugin behaves like the list_field plugin.
    return 'list_field';
  }
}

/**
 * Allow validating an array index value for a specific plugin.
 *
 * @param int|string $index
 *   The array index value to check.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 *
 * @return true|void
 *   TRUE if the index value is valid for that filter plugin.
 */
function hook_views_filters_summary_valid_index($index, $filter) {
  if ($filter->getPluginId() === 'administrative_area') {
    // Array is similar to: ['AL' => 'AL', 'AK' => 'AK'].
    return TRUE;
  }
}

/**
 * Alter the exposed form ID for a Views filter summary.
 *
 * Allows developers to change the form ID used for exposed filters in Views,
 * for example when embedding Views in an Entity Browser.
 *
 * @param string $exposed_form_id
 *   The exposed form ID, passed by reference.
 * @param \Drupal\views\ViewExecutable $view
 *   The view instance.
 * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display_handler
 *   The display handler instance.
 */
function hook_views_filters_summary_exposed_form_id_alter(
  string &$exposed_form_id,
  Drupal\views\ViewExecutable $view,
  Drupal\views\Plugin\views\display\DisplayPluginBase $display_handler,
) {
  if ($display_handler->getPluginId() === 'entity_browser') {
    // Entity Browser embeds the View’s exposed filters in its own form
    // (no separate <form id="views-exposed-form-...">).
    // The Entity Browser form ID is <form id="entity-browser-...">.
    $exposed_form_id = 'entity-browser-';
  }
}

/**
 * Alter the value for a filter in the summary.
 *
 * Allows developers to change the original value of a filter before
 * it is displayed in the Views Filters Summary.
 *
 * @param mixed $value
 *   The value for the filter, passed by reference.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 */
function hook_views_filters_summary_filter_value_alter(
  mixed &$value,
  Drupal\views\Plugin\views\filter\FilterPluginBase $filter,
) {
  // Example taken from the views_filters_summary module.
  // For some reason, the User Permissions plugin does not properly
  // populate the filter value.
  if ($filter->getPluginId() === 'user_permissions') {
    $inputs = $filter->view->getExposedInput();
    $value = $inputs[$filter->options['id']];
  }
}

/**
 * Alter the label for a filter value in the summary.
 *
 * Allows developers to change the label displayed for a filter value
 * in the Views Filters Summary.
 *
 * @param string $label
 *   The label for the filter value, passed by reference.
 * @param string $value
 *   The value for the filter, passed by reference.
 * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
 *   The view filter plugin instance.
 */
function hook_views_filters_summary_filter_value_label_alter(
  string &$label,
  string &$value,
  Drupal\views\Plugin\views\filter\FilterPluginBase $filter,
) {
  // If the filter is a boolean filter and the filter is exposed as a BEF
  // single checkbox, then change the label to the exposed label.
  if ($filter->getPluginId() === 'boolean') {
    $exposed_form = $filter->view->display_handler->getOption('exposed_form');
    $bef_options = $exposed_form['options']['bef']['filter'] ?? [];
    if (isset($bef_options[$filter->field]['plugin_id'])
      && $bef_options[$filter->field]['plugin_id'] === 'bef_single') {
      $label = $value ? $filter->options['expose']['label'] : NULL;
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
