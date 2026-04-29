<?php

namespace Drupal\views_filters_summary\Plugin\views\area;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\style\DefaultSummary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_filters_summary")
 */
class ViewsFiltersSummary extends Result {

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ViewsFiltersSummary object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The bundle info service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $translation_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    LoggerChannelFactoryInterface $logger_factory,
    LanguageManagerInterface $language_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->translationManager = $translation_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->loggerFactory = $logger_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('logger.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    $options['filters'] = ['default' => NULL];
    $options['group_values'] = ['default' => NULL];
    $options['show_labels'] = ['default' => FALSE];
    $options['show_remove_link'] = ['default' => TRUE];
    $options['show_reset_link'] = ['default' => FALSE];
    $options['filters_reset_link_title'] = ['default' => 'Reset'];
    $options['filters_summary_separator'] = ['default' => ', '];
    $options['filters_summary_prefix'] = ['default' => 'for '];
    $options['filters_result_label'] = [
      'default' => [
        'plural' => 'results',
        'singular' => 'result',
      ],
    ];
    $options['content'] = [
      'default' => $this->t('Displaying @total @result_label @exposed_filter_summary'),
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(
    &$form,
    FormStateInterface $form_state,
  ): void {
    parent::buildOptionsForm($form, $form_state);

    $form['filters'] = [
      '#type' => 'select',
      '#title' => $this->t('Filters'),
      '#multiple' => TRUE,
      '#options' => $this->getFilterOptions(),
      '#default_value' => $this->options['filters'],
      '#description' => $this->t(
        'Choose the filters, if none are selected, all will be used.'
      ),
    ];
    $form['filters_summary_prefix'] = [
      '#title' => $this->t('Exposed Filters Summary Prefix'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_summary_prefix'],
      '#description' => $this->t(
        'Shows a prefix along with the exposed filters summary.'
      ),
    ];
    $form['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display labels'),
      '#default_value' => $this->options['show_labels'],
      '#description' => $this->t(
        'If checked, the labels will be displayed, otherwise just the value is
        printed.'
      ),
    ];
    $form['group_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group multi-value filters'),
      '#default_value' => $this->options['group_values'],
      '#description' => $this->t(
        'If checked, multi value filters will be grouped together under a single label.'
      ),
    ];
    $form['show_remove_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show remove filter link'),
      '#default_value' => $this->options['show_remove_link'],
      '#description' => $this->t(
        'If checked, a remove link for each filter will be shown.'
      ),
    ];
    $form['show_reset_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset filter link'),
      '#description' => $this->t('If checked, a reset filter link will be shown.'),
      '#default_value' => $this->options['show_reset_link'],
    ];
    $form['filters_reset_link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exposed Filter Reset Link Title'),
      '#description' => $this->t('Set the reset filter link title.'),
      '#default_value' => $this->options['filters_reset_link_title'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="options[show_reset_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['filters_summary_separator'] = [
      '#title' => $this->t('Exposed Filter Summary Separator'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_summary_separator'],
      '#description' => $this->t(
        'How would you like your exposed filter summary items to appear?
        Example: A comma (, ) would produce Apple, Orange, Pear'
      ),
    ];
    $form['filters_result_label'] = [
      '#type' => 'details',
      '#title' => $this->t('Exposed Filter Results Label'),
      '#open' => TRUE,
    ];
    $form['filters_result_label']['singular'] = [
      '#title' => $this->t('Singular'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_result_label']['singular'],
      '#description' => $this->t(
        'Enter the singular label for the type of result being shown.'
      ),
    ];
    $form['filters_result_label']['plural'] = [
      '#title' => $this->t('Plural'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filters_result_label']['plural'],
      '#description' => $this->t(
        'Enter the plural label for the type of result being shown.'
      ),
    ];

    if (isset($form['content'])) {
      $item_list = [
        '#theme' => 'item_list',
        '#items' => [
          '@result_label -- the label of the type result being displayed',
          '@exposed_filter_summary -- the summary of selected exposed filters',
        ],
      ];
      $form['content']['#description'] .= $this->getRenderer()->render(
        $item_list
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE): array {
    if (
      !isset($this->options['content'])
      || $this->view->style_plugin instanceof DefaultSummary
    ) {
      return [];
    }

    try {
      $replacements = $this->buildReplacements();

      if (!empty($replacements['@total']) || !empty($this->options['empty'])) {
        return [
          '#markup' => str_replace(
            array_keys($replacements),
            array_values($replacements),
            (string) $this->options['content']
          ),
          '#attached' => [
            'library' => ['views_filters_summary/views_filters_summary'],
          ],
        ];
      }
    }
    catch (\Exception $exception) {
      $logger = $this->loggerFactory->get('views_filters_summary');
      Error::logException($logger, $exception);
    }

    return [];
  }

  /**
   * Build the filter summary.
   *
   * @return array
   *   An array of the filter summary.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildFilterSummary(): array {
    $summary = [];

    foreach ($this->getFilterDefinitions() as $definition) {
      if (!isset($definition['id'], $definition['value'])) {
        continue;
      }
      $id = $definition['id'];
      $value = $definition['value'];
      $label = $definition['label'];

      if (is_array($value)) {
        if (
          isset($this->options['group_values'])
          && $this->options['group_values']
        ) {
          $info = reset($value);

          if (!isset($info['id'], $info['value'])) {
            continue;
          }
          $summary[] = $this->buildFilterSummaryGroupedItem(
            $id, $label, $value
          );
        }
        else {
          foreach ($value as $info) {
            if (!isset($info['id'], $info['value'])) {
              continue;
            }
            $summary[] = $this->buildFilterSummaryItem(
              $id, $label, $info['value'], $info['raw']
            );
          }
        }
      }
      else {
        $summary[] = $this->buildFilterSummaryItem(
          $id, $label, $value
        );
      }
    }

    return $summary;
  }

  /**
   * Build the filter summary item.
   *
   * @param string $id
   *   The filter item identifier.
   * @param string $label
   *   The filter item label.
   * @param string $value
   *   The filter item value.
   * @param string|int|null $value_raw
   *   The filter item value raw.
   *
   * @return array
   *   A structured filter summary item array.
   */
  protected function buildFilterSummaryItem(
    string $id,
    string $label,
    string $value,
    string|int|null $value_raw = NULL,
  ): array {
    $input = $value_raw ?? $value;
    $item = [
      'id' => $id,
      'label' => $label,
      'value' => $value,
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('X'),
        '#url' => Url::fromUserInput('/'),
        '#attributes' => [
          'class' => ['remove-filter', 'disabled'],
          'data-remove-selector' => "$id:$input",
          'aria-label' => $this->t("Clear @value", ['@value' => $value]),
        ],
      ],
    ];
    // Invoke hook_views_filters_summary_item_alter().
    $this->moduleHandler->alter('views_filters_summary_item', $item);
    return $item;
  }

  /**
   * Build the filter summary grouped item.
   *
   * @param string $id
   *   The filter item identifier.
   * @param string $label
   *   The filter item label.
   * @param array $values
   *   The filter item values.
   *
   * @return array
   *   A structured filter summary item array.
   */
  protected function buildFilterSummaryGroupedItem(
    string $id,
    string $label,
    array $values,
  ): array {
    $item = [
      'id' => $id,
      'label' => $label,
      'groups' => [],
    ];
    foreach ($values as $value) {
      $item['groups'][] = $this->buildFilterSummaryItem(
        $id, $label, $value['value'], $value['raw']
      );
    }
    return $item;
  }

  /**
   * Define the filter format replacements.
   *
   * @return array
   *   An array of replacement variables.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  protected function defineReplacements(): array {
    $view = $this->view;

    $replacements = [
      'label' => $this->view->storage->label(),
      'per_page' => (int) $view->getItemsPerPage(),
      'page_count' => 1,
      'current_page' => (int) $view->getCurrentPage() + 1,
      'total' => $view->total_rows ?? count($view->result),
    ];
    $replacements['start_offset'] = empty($replacements['total']) ? 0 : 1;

    $replacements['end'] = $replacements['total'];
    $replacements['start'] = $replacements['start_offset'];

    if ($replacements['per_page'] !== 0) {
      $replacements['page_count'] = (int) ceil($replacements['total'] / $replacements['per_page']);
      $total_count = $replacements['current_page'] * $replacements['per_page'];

      if ($total_count > $replacements['total']) {
        $total_count = $replacements['total'];
      }
      $replacements['end'] = $total_count;
      $replacements['start'] = ($replacements['current_page'] - 1) * $replacements['per_page'] + $replacements['start_offset'];
    }
    $replacements['current_record_count'] = ($replacements['end'] - $replacements['start']) + $replacements['start_offset'];

    $replacements['result_label'] = $this->translationManager->formatPlural(
      $replacements['total'],
      $this->options['filters_result_label']['singular'],
      $this->options['filters_result_label']['plural']
    );
    $replacements['exposed_filter_summary'] = $this->buildFilterSummaryMarkup();

    // Invoke hook_views_filters_summary_replacements_alter().
    $this->moduleHandler->alter('views_filters_summary_replacements', $replacements, $view);

    return $replacements;
  }

  /**
   * Checks if the view is in preview mode.
   *
   * @return bool
   *   TRUE if the view is in preview mode, FALSE otherwise.
   */
  protected function isPreviewMode() {
    return $this->view->live_preview ?? FALSE;
  }

  /**
   * Gets the exposed form ID for the current view display.
   *
   * Allows other modules to alter the form ID via
   * hook_views_filters_summary_exposed_form_id_alter().
   *
   * @return string
   *   The exposed form ID.
   */
  protected function getExposedFormId() {
    if ($this->isPreviewMode()) {
      $exposed_form_id = 'views-ui-preview-form';
    }
    else {
      // By default, (for example, on a Page display), a View’s exposed filters
      // are rendered inside a form with an ID of the form:
      // <form id="views-exposed-form-<VIEW_ID>-<DISPLAY_ID>...">.
      $exposed_form_id = 'views-exposed-form-' . $this->view->id() . '-' . $this->view->current_display;

      $this->moduleHandler->alter(
        'views_filters_summary_exposed_form_id',
        $exposed_form_id,
        $this->view,
        $this->displayHandler,
      );
    }
    return $exposed_form_id;
  }

  /**
   * Build the filter summary markup.
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   *   The filter summary markup.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildFilterSummaryMarkup(): ?MarkupInterface {
    if ($summary = $this->buildFilterSummary()) {
      $element = [
        '#theme' => 'views_filters_summary',
        '#summary' => $summary,
        '#options' => [
          'use_ajax' => $this->view->ajaxEnabled(),
          'show_label' => $this->options['show_labels'],
          'show_remove_link' => $this->options['show_remove_link'],
          'show_reset_link' => $this->options['show_reset_link'],
          'has_group_values' => $this->options['group_values'],
          'reset_link' => [
            'title' => $this->options['filters_reset_link_title'],
            'filter_ids' => array_unique(array_map(fn ($item) => $item['id'], $summary)),
          ],
          'filters_summary' => [
            'prefix' => $this->options['filters_summary_prefix'],
            'separator' => $this->options['filters_summary_separator'],
          ],
        ],
        '#exposed_form_id' => Html::cleanCssIdentifier($this->getExposedFormId()),
      ];
      // Handle backward compatibility.
      if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
        return $this->getRenderer()->renderInIsolation($element);
      }
      else {
        // @phpstan-ignore-next-line as it is deprecated in D10.3 and removed from D12.
        return $this->getRenderer()->renderPlain($element);
      }
    }

    return NULL;
  }

  /**
   * Build the filter format replacements.
   *
   * @return array
   *   An array of formatted replacements.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildReplacements(): array {
    $variables = [];

    foreach ($this->defineReplacements() as $key => $value) {
      $variables["@$key"] = $value;
    }

    return $variables;
  }

  /**
   * Get the filter definitions.
   *
   * @return array
   *   An array of filter definitions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFilterDefinitions(): array {
    $definitions = [];

    foreach ($this->view->filter as $filter) {
      if (
        !$filter->isExposed()
        || !$this->isSelectedFilter($filter)
        || !$this->hasValidFilterValue($filter)
        || !$this->hasValidExposedInput($filter)
      ) {
        continue;
      }
      $definition = $this->buildFilterDefinition($filter);
      if (!$this->hasValidDefinitionValue($definition)) {
        continue;
      }
      $definitions[] = $definition;
    }

    return $definitions;
  }

  /**
   * Check if the value is not empty or equal to zero.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   True if the value is not empty or equal to zero.
   */
  protected function isValidValue(mixed $value): bool {
    return !empty($value) || is_numeric($value);
  }

  /**
   * Get the filter operator prefix for display.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter object.
   * @param string $value
   *   The filter value.
   *
   * @return string|null
   *   The filter value label.
   */
  protected function getFilterValueLabel(FilterPluginBase $filter, string $value): ?string {
    $label = match ($filter->getPluginId()) {
      'boolean' => $filter->valueOptions[$value] ?? $value,
      default => $value,
    };
    $label = $this->getValueLabelFromOperator($filter->operator, $label);
    // Invoke hook_views_filters_summary_filter_value_label_alter().
    $this->moduleHandler->alter('views_filters_summary_filter_value_label', $label, $value, $filter);
    return $label;
  }

  /**
   * Get the formatted label from a given operator.
   *
   * @param string $operator
   *   The operator to check.
   * @param string $label
   *   The base label.
   *
   * @return string
   *   The formatted label.
   */
  protected function getValueLabelFromOperator(string $operator, string $label) {
    return match ($operator) {
      '!=' => $this->t('Not @label', ['@label' => $label]),
      '<' => $this->t('Less than @label', ['@label' => $label]),
      '<=' => $this->t('Less than or equal to @label', ['@label' => $label]),
      '>' => $this->t('Greater than @label', ['@label' => $label]),
      '>=' => $this->t('Greater than or equal to @label', ['@label' => $label]),
      'starts' => $this->t('Starts with @label', ['@label' => $label]),
      'contains' => $this->t('Contains @label', ['@label' => $label]),
      'not contains' => $this->t('Does not contain @label', ['@label' => $label]),
      'ends' => $this->t('Ends with @label', ['@label' => $label]),
      default => $label,
    };
  }

  /**
   * Build the filter definition.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The view filter plugin instance.
   *
   * @return array
   *   An array of definition properties.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildFilterDefinition(FilterPluginBase $filter): array {

    $original_value = $filter->value['value'] ?? $filter->value;

    // Invoke hook_views_filters_summary_filter_value_alter().
    $this->moduleHandler->alter('views_filters_summary_filter_value', $original_value, $filter);

    $processed_value = NULL;

    // Special handling of grouped filters.
    if ($filter->options['is_grouped']) {

      // The Views module does not properly populate the grouped filter values.
      // So we have to use the exposed input values.
      $exposed_inputs = $filter->view->getExposedInput();
      $filter_input = $exposed_inputs[$filter->options['group_info']['identifier']];
      $original_value = is_array($filter_input) ? $filter_input : [$filter_input];

      $values = [];
      $filter_group_items = $filter->options['group_info']['group_items'];
      foreach ($original_value as $index => $value) {
        if (isset($filter_group_items[$value])) {
          $group_item = $filter_group_items[$value];
          $values[] = [
            'id' => $index,
            'raw' => $value,
            'value' => $group_item['title'],
          ];
        }
      }
      $processed_value = $values;

    }
    else {

      $plugin_alias = $filter->getPluginId();

      // Invoke hook_views_filters_summary_plugin_alias().
      $aliases = $this->moduleHandler->invokeAll('views_filters_summary_plugin_alias', [$filter]);
      // Check if some module has provided an alias for that filter plugin.
      foreach ($aliases as $alias) {
        if (is_string($alias)) {
          $plugin_alias = $alias;
          break;
        }
      }

      switch ($plugin_alias) {
        case 'taxonomy_index_tid':
        case 'taxonomy_index_tid_depth':
          if (is_array($original_value)) {
            $values = [];
            $storage = $this->entityTypeManager->getStorage('taxonomy_term');
            // Get the current user's language.
            $current_language = $this->languageManager->getCurrentLanguage()->getId();
            foreach ($original_value as $index => $term) {
              if ($term = $storage->load($term)) {
                // Get the term translation in the current user's language.
                if ($term->hasTranslation($current_language)) {
                  $translated_term = $term->getTranslation($current_language);
                }
                else {
                  // Fallback to the default language if no translation exists.
                  $translated_term = $term;
                }
                $values[] = [
                  'id' => $index,
                  'raw' => $term->id(),
                  'value' => $translated_term->label(),
                ];
              }
            }
            $processed_value = $values;
          }
          break;

        case 'bundle':
          if (is_array($original_value)) {
            if ($entity_type = $filter->getEntityType()) {
              $values = [];
              $types = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
              foreach ($original_value as $index => $value) {
                if (!isset($types[$value])) {
                  continue;
                }
                $values[] = [
                  'id' => $index,
                  'raw' => $value,
                  'value' => $types[$value]['label'],
                ];
              }
              $processed_value = $values;
            }
          }
          break;

        case 'user_name':
          if (is_array($original_value)) {
            $values = [];
            foreach ($original_value as $index => $value) {
              if (!$this->isValidArrayValue($filter, $index, $value)) {
                continue;
              }
              $user = User::load($value);
              if ($user) {
                $values[] = [
                  'id' => $index,
                  'raw' => (string) $value,
                  'value' => (string) $user->getDisplayName(),
                ];
              }
            }
            $processed_value = $values;
          }
          break;

        case 'list_field':
          $original_value = is_array($original_value) ? $original_value : [$original_value];
          if (method_exists($filter, 'getValueOptions')) {
            $values = [];
            $value_options = $filter->getValueOptions();
            if (!empty($value_options) && is_array($value_options)) {
              // Support both flat arrays and 2D grouped arrays of options.
              // If $value_options is grouped (contains subarrays), flatten it.
              // Since list field keys must be unique (regardless of grouping),
              // this is safe.
              $flattened_value_options = [];
              foreach ($value_options as $key => $value) {
                if (is_array($value)) {
                  foreach ($value as $group_value_key => $group_value) {
                    $flattened_value_options[$group_value_key] = $group_value;
                  }
                }
                else {
                  $flattened_value_options[$key] = $value;
                }
              }
              foreach ($original_value as $index => $value) {
                if (!$this->isValidArrayValue($filter, $index, $value)
                  || !isset($flattened_value_options[$value])) {
                  continue;
                }
                $values[] = [
                  'id' => $index,
                  'raw' => $value,
                  'value' => $flattened_value_options[$value],
                ];
              }
            }
            $processed_value = $values;
          }
          break;

        default:
          if ($filter->operator === 'between' || $filter->operator === 'not between') {
            $min_value = $filter->value['min'] ?? NULL;
            $max_value = $filter->value['max'] ?? NULL;
            if ($this->isValidValue($min_value) || $this->isValidValue($max_value)) {
              if (empty($max_value)) {
                $value = $this->getValueLabelFromOperator('>=', (string) $min_value);
              }
              elseif (empty($min_value)) {
                $value = $this->getValueLabelFromOperator('<=', (string) $max_value);
              }
              else {
                $value = $min_value . '-' . $max_value;
              }
              $values[] = [
                'id' => 0,
                'raw' => 'min|max',
                'value' => $value,
              ];
              $processed_value = $values;
            }
          }
          else {
            $original_value = is_array($original_value) ? $original_value : [$original_value];
            $values = [];
            foreach ($original_value as $index => $item_value) {
              if (!$this->isValidArrayValue($filter, $index, $item_value)) {
                continue;
              }
              $value = (string) $item_value;
              $value_label = $this->getFilterValueLabel($filter, $value);
              if (!empty($value_label)) {
                $values[] = [
                  'id' => $index,
                  'raw' => $value,
                  'value' => $value_label,
                ];
              }
            }
            $processed_value = $values;
          }
          break;
      }
    }

    $info = [
      'id' => $filter->exposedInfo()['value'] ?? NULL,
      'label' => $this->getFilterLabel($filter),
      'value' => $processed_value,
    ];

    // Invoke hook_views_filters_summary_info_alter().
    $this->moduleHandler->alter('views_filters_summary_info', $info, $filter);

    return $info;
  }

  /**
   * Get the views filter label.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The views filter instance.
   *
   * @return string|null
   *   The views filter label.
   */
  protected function getFilterLabel(
    FilterPluginBase $filter,
  ): ?string {
    if ($filter->options['is_grouped']) {
      if (!empty($filter->options['group_info']['label'])) {
        return $filter->options['group_info']['label'];
      }
    }
    return $filter->options['expose']['label']
      ?? $filter->definition['title short']
      ?? $filter->definition['title']
      ?? NULL;
  }

  /**
   * Get the views filter options.
   *
   * @return array
   *   An array of the filter options.
   */
  protected function getFilterOptions(): array {
    $options = [];

    $this->view->initHandlers();
    foreach ($this->view->filter as $id => $handler) {
      if ($handler->isExposed()) {
        $options[$id] = $handler->adminLabel();
      }
    }

    return $options;
  }

  /**
   * Check that the filter value is equal to zero or not empty.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter to validate.
   *
   * @return bool
   *   True if the filter has a valid value.
   */
  protected function hasValidFilterValue(FilterPluginBase $filter) {
    // We want to keep the numerical filter values equal to zero.
    return $this->isValidValue($filter->value);
  }

  /**
   * Check the filter definition value.
   *
   * @param array $definition
   *   The filter definition to check value validity from.
   *
   * @return bool
   *   True if definition value is valid.
   */
  protected function hasValidDefinitionValue(array $definition): bool {
    return $this->isValidValue($definition['value']);
  }

  /**
   * Check that a filter has a valid corresponding exposed input.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter to validate.
   *
   * @return bool
   *   True if a corresponding valid exposed input has been found.
   */
  protected function hasValidExposedInput(FilterPluginBase $filter): bool {
    $inputs = $filter->view->getExposedInput();
    if ($filter->options['is_grouped']) {
      $identifier = $filter->options['group_info']['identifier'];
      $default = $filter->options['group_info']['default_group'];
    }
    else {
      $identifier = $filter->options['expose']['identifier'];
      $default = 'All';
    }
    return isset($inputs[$identifier]) && $inputs[$identifier] != $default;
  }

  /**
   * Check that the filter is selected in the configuration.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter to check for selection.
   *
   * @return bool
   *   True if the filter is present in the configuration selection.
   */
  protected function isSelectedFilter(FilterPluginBase $filter): bool {
    $filters = $this->options['filters'];
    return empty($filters) || in_array($filter->options['id'], $filters);
  }

  /**
   * Check if the value index is valid.
   *
   * We want to exclude 'type' for some filters like date, for example.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The array value filter.
   * @param int|string $index
   *   The index in the array value.
   *
   * @return bool
   *   True if the array value index is valid, false otherwise.
   */
  protected function isValidIndex(FilterPluginBase $filter, int|string $index): bool {
    switch ($filter->pluginId) {
      case 'language':
        // Language filter arrays are similar to: ['fr' => 'fr', 'en' => 'en'].
        $is_valid = TRUE;
        break;

      case 'list_field':
        // Text list filter arrays can be similar to:
        // ['cat' => 'Cat', 'dog' => 'Dog'].
        $is_valid = is_numeric($index) || $index !== '';
        break;

      default:
        // By default, we want all indexes to be numeric values.
        $is_valid = is_numeric($index);
        break;
    }

    // If not valid, check if some other modules handle that plugin type.
    if (!$is_valid) {
      // Invoke hook_views_filters_summary_valid_index().
      $results = $this->moduleHandler->invokeAll('views_filters_summary_valid_index', [$index, $filter]);
      // Check that at least one module says the index value is valid.
      $is_valid = in_array(TRUE, $results, TRUE);
    }

    return $is_valid;
  }

  /**
   * Check if a filter array value is valid.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter.
   * @param int|string $index
   *   The value index in an array.
   * @param mixed $value
   *   The array value.
   *
   * @return bool
   *   True if valid array value, false otherwise.
   */
  protected function isValidArrayValue(
    FilterPluginBase $filter,
    int|string $index,
    mixed $value,
  ): bool {
    return $this->isValidIndex($filter, $index) && $this->isValidValue($value);
  }

}
