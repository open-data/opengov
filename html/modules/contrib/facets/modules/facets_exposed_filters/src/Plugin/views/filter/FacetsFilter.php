<?php

namespace Drupal\facets_exposed_filters\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Hierarchy\HierarchyPluginBase;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\facets\Processor\SortProcessorInterface;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Drupal\facets_exposed_filters\ExposedFacetBuildState;
use Drupal\facets_exposed_filters\FacetsExposedFiltersHelper;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides exposing facets as a filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("facets_filter")
 */
class FacetsFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $no_operator = FALSE;

  /**
   * Stores the facet results after the query is executed.
   *
   * @var \Drupal\facets\Result\ResultInterface[]
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $facet_results = [];

  /**
   * Stores raw facet results for slider snap-value extraction.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $facet_slider_raw_results = [];

  /**
   * The query type plugin manager.
   *
   * @var \Drupal\facets\QueryType\QueryTypePluginManager
   */
  protected $queryTypePluginManager;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\facets\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * Constructs a new facets filter plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryTypePluginManager $query_type_plugin_manager, ElementInfoManagerInterface $element_info_manager, ConfigFactoryInterface $config_factory, RequestStack $request_stack, ProcessorPluginManager $processor_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queryTypePluginManager = $query_type_plugin_manager;
    $this->elementInfoManager = $element_info_manager;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->processorPluginManager = $processor_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.query_type'),
      $container->get('element_info'),
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('plugin.manager.facets.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['exposed'] = ['default' => TRUE];
    $options['expose']['contains']['identifier']['default'] = $this->configuration["search_api_field_identifier"];
    $options["expose"]["contains"]["label"]["default"] = $this->configuration["search_api_field_label"];
    $options['expose']['contains']['multiple'] = ['default' => TRUE];
    $options['hierarchy'] = ['default' => FALSE];
    $options['label_display']['default'] = BlockPluginInterface::BLOCK_LABEL_VISIBLE;
    $options['facet']['contains']['missing'] = ['default' => FALSE];
    $options['facet']['contains']['missing_label'] = ['default' => 'others'];
    $options['facet']['contains']['show_numbers'] = ['default' => FALSE];
    $options['facet']['contains']['show_only_one_result'] = ['default' => FALSE];
    $options['facet']['contains']['depends_on_exposed_filter'] = ['default' => ''];
    $options['facet']['contains']['min_count'] = ['default' => 1];
    $options['facet']['contains']['query_operator'] = ['default' => 'or'];
    $options['facet']['contains']['hard_limit'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form["expose"]["remember"]['#access'] = FALSE;
    $form["expose"]["remember_roles"]['#access'] = FALSE;

    // @todo Not supported for now, needs work.
    $form["expose"]["required"]['#access'] = FALSE;

    // Expose settings are not needed for this filter. It does not make sense to
    // create a facet filter and not expose it.
    $form["expose_button"]['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    return 'Facet: ' . $this->options["expose"]["label"] . ' (' . $this->options["expose"]["identifier"] . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [];

    // Extra checks when in views UI.
    if (isset($_POST["form_id"]) && $_POST["form_id"] === 'view_preview_form') {
      if (!$this->isViewAndDisplaySaved()) {
        // Set warning.
        $this->messenger()
          ->addWarning('The current display has not been saved yet. You need to save this display before facet filters are visible.');
        return $form;
      }
    }

    if (!$this->isExposedFilterDependencySatisfied()) {
      return $form;
    }

    // Due to how views works, this form is built before results are queried.
    // We render this form again in the views_post_execute hook implementation
    // once the Views query is executed and facet results are available.
    if (!isset($this->view->filter[$this->options["id"]]->facet_results)) {
      return $form;
    }

    // Retrieve the processed facet if already handled in the current request.
    $processed_facet = FacetsExposedFiltersHelper::getProcessedFacet($this->view->id(), $this->view->current_display, $this->options["id"]);

    // Empty facet results, return empty form.
    if (!FacetsExposedFiltersHelper::hasViewPostExecuted($this->view->id(), $this->view->current_display) && !$processed_facet) {
      return $form;
    }

    // A separate exposed-filter block instance has no Views query to process.
    // Empty facets should remain hidden rather than dereferencing a NULL query.
    if (!$processed_facet && $this->query === NULL) {
      return $form;
    }

    if ($processed_facet) {
      $facet = $processed_facet;
    }
    else {
      /** @var \Drupal\facets\FacetInterface $facet */
      $facet = $this->getFacet();

      $active_facet_values = $this->getActiveFacetValues();
      $facet->setActiveItems($active_facet_values);
      $this->syncFacetActiveItemsToExposedState($facet, TRUE);

      foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_PRE_QUERY) as $processor) {
        if ($processor instanceof PreQueryProcessorInterface) {
          $processor->preQuery($facet);
        }

        $this->syncFacetActiveItemsToExposedState($facet, TRUE);

        if (ExposedFacetBuildState::shouldSkipFacet($facet)) {
          return;
        }
      }

      // Processors may normalize or clear active items. Preserve that updated
      // state for the rest of the exposed-form rebuild instead of restoring the
      // raw request values later.
      $active_facet_values = $this->normalizeActiveFacetValues(
        $facet->getActiveItems(),
      );

      // Load the query_type plugin and execute build.
      /** @var \Drupal\facets\QueryType\QueryTypeInterface $query_type_plugin */
      $query_type_plugin = $this->queryTypePluginManager->createInstance(
        $facet->getQueryType(),
        [
          'query' => $this->query->getSearchApiQuery(),
          'facet' => $facet,
          'results' => $this->view->filter[$this->options["id"]]->facet_results ?? [],
        ]
      );
      $query_type_plugin->build();

      // Skip facet processing and form rendering if there are no results.
      // Active range facets still need post-query processing so the slider can
      // render the selected min/max values even when no new buckets are
      // returned for the narrowed result set.
      if (!$facet->getResults() && !$this->hasExposedRangeKeys($facet->getActiveItems())) {
        return;
      }

      // Trigger post query stage.
      $processors = $facet->getProcessorsByStage(ProcessorInterface::STAGE_POST_QUERY);
      foreach ($processors as $processor) {
        $processor->postQuery($facet);

        if (ExposedFacetBuildState::shouldSkipFacet($facet)) {
          return;
        }
      }

      // Trigger build stage.
      $processors = $facet->getProcessorsByStage(ProcessorInterface::STAGE_BUILD);
      foreach ($processors as $processor) {
        $facet->setResults($processor->build($facet, $facet->getResults()));

        if (ExposedFacetBuildState::shouldSkipFacet($facet)) {
          return;
        }
      }

      // Allow processors to sort the results.
      $active_sort_processors = [];
      foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_SORT) as $processor) {
        $active_sort_processors[] = $processor;
      }
      if (!empty($active_sort_processors)) {
        $facet->setResults($this->sortFacetResults($active_sort_processors, $facet->getResults()));
      }
      $facet->setResults($this->applyShowOnlyOneResult($facet, $facet->getResults()));
      $facet->setActiveItems(array_values($active_facet_values));

      // Store the processed facet so we can access it later (e.g. in an exposed
      // form rendered as a block).
      FacetsExposedFiltersHelper::getProcessedFacet($this->view->id(), $this->view->current_display, $this->options["id"], $facet);
    }

    // We need to merge the existing #process callbacks with our own.
    $select_element = $this->elementInfoManager->getInfo('select');

    $options = $this->buildOptions($facet->getResults(), $facet);
    $options = $this->filterOptionsByActiveValues($facet, $options);

    $this->value = $facet->getActiveItems();
    // Store processed results so other modules can use these.
    $this->facet_results = $facet->getResults();
    $form['value'] = [
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => $this->options["expose"]["multiple"],
      '#process' => array_merge($select_element["#process"], [[FacetsExposedFiltersHelper::class, 'removeValidation']]),
      '#facets_show_only_one_result' => $facet->getShowOnlyOneResult(),
      '#facets_active_option_values' => array_map('strval', array_values($facet->getActiveItems())),
    ];

    $dependency_attributes = $this->getExposedFilterDependencyAttributes();
    if ($dependency_attributes !== []) {
      $form['value']['#attributes'] = array_merge(
        $form['value']['#attributes'] ?? [],
        $dependency_attributes,
      );
      $form['value']['#wrapper_attributes'] = array_merge(
        $form['value']['#wrapper_attributes'] ?? [],
        $dependency_attributes,
      );
      $form['value']['#attached']['library'][] = 'facets_exposed_filters/dependent_filters';
    }

    $exposed_form_type = $this->displayHandler->getPlugin('exposed_form')->getPluginId();
    if ($exposed_form_type == 'bef') {
      $form['value']['#process'] = [[FacetsExposedFiltersHelper::class, 'removeValidation']];
    }
  }

  /**
   * Helper function which transforms results into options.
   *
   * This can be called recursively to build a hierarchy.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The array of result objects.
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to build the options.
   * @param int $depth
   *   The level of depth.
   *
   * @return array
   *   The built options to be used on a select form element.
   */
  private function buildOptions(array $results, FacetInterface $facet, $depth = 0): array {
    $hierarchy_prefix = "";
    for ($i = 0; $i < $depth; $i++) {
      $hierarchy_prefix .= "-";
    }
    $options = [];
    foreach ($results as $result) {
      $label = $result->getDisplayValue();
      if ($this->options["facet"]["show_numbers"] && $result->getCount() !== FALSE) {
        $label .= ' (' . $result->getCount() . ')';
      }
      $options[$this->getExposedOptionValue($facet, $result)] = $hierarchy_prefix . $label;
      if ($facet->getUseHierarchy()) {
        $children = $result->getChildren();
        if ($children && ($facet->getExpandHierarchy() || $result->isActive() || $result->hasActiveChildren())) {
          $options = $options + $this->buildOptions($children, $facet, $depth + 1);
        }
      }
    }
    return $options;
  }

  /**
   * Gets the submitted exposed-filter value for a facet result.
   *
   * Missing results need to submit the encoded "invert these values" token that
   * the Search API facet query type understands. Submitting the raw `!` marker
   * makes the exposed filter look active but queries for a literal `!` value,
   * which returns no results.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being rendered.
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The facet result option.
   *
   * @return string
   *   The exposed option value to submit.
   */
  private function getExposedOptionValue(FacetInterface $facet, $result): string {
    if (!$result->isMissing()) {
      return (string) $result->getRawValue();
    }

    foreach ((array) $facet->getActiveItems() as $active_item) {
      if (is_string($active_item) && str_starts_with($active_item, '!(')) {
        return $active_item;
      }
    }

    $missing_filters = $result->getMissingFilters();

    if ($missing_filters === []) {
      return (string) $result->getRawValue();
    }

    return '!(' . implode(',', $missing_filters) . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    // Modules like views_dependent_filters alter the exposed option to ignore
    // the filter when hidden. We need to check for this.
    return $this->isExposed() && $this->isExposedFilterDependencySatisfied($input);
  }

  /**
   * {@inheritdoc}
   */
  public function canGroup() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $facet = $this->getFacet();

    if (!$this->isExposedFilterDependencySatisfied()) {
      $facet->setActiveItems([]);
      $this->syncFacetActiveItemsToExposedState($facet);
      ExposedFacetBuildState::skipFacet($facet);
      return;
    }

    ExposedFacetBuildState::allowFacet($facet);

    $active_values = $this->getActiveFacetValues();
    $facet->setActiveItems($active_values);
    $this->syncFacetActiveItemsToExposedState($facet, TRUE);

    foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_PRE_QUERY) as $processor) {
      if ($processor instanceof PreQueryProcessorInterface) {
        $processor->preQuery($facet);
      }

      $this->syncFacetActiveItemsToExposedState($facet);

      if (ExposedFacetBuildState::shouldSkipFacet($facet)) {
        return;
      }
    }

    /** @var \Drupal\facets\QueryType\QueryTypeInterface $query_type_plugin */
    $query_type_plugin = $this->queryTypePluginManager->createInstance(
      $facet->getQueryType(),
      [
        'query' => $this->query->getSearchApiQuery(),
        'facet' => $facet,
      ]
    );
    $query_type_plugin->execute();
  }

  /**
   * Expose configuration for facets_exposed_filters_views_post_execute().
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * Returns TRUE if current view and display has been saved.
   */
  protected function isViewAndDisplaySaved() {
    // Check if the view has been saved yet.
    if ($this->view->storage->get('base_field') !== 'search_api_id') {
      return FALSE;
    }
    // Check if the current display has been saved yet.
    $display_saved_config = $this->configFactory->get('views.view.' . $this->view->id())
      ->get('display');
    if (!isset($display_saved_config[$this->view->current_display])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {

    if (!$this->isViewAndDisplaySaved()) {
      $form['message']['#markup'] = $this->t('The current display has not been saved yet. You need to save this display before you can configure the facet settings.');
      return $form;
    }

    $facet = $this->getFacet();
    $all_processors = $facet->getProcessors(FALSE);
    unset($all_processors["url_processor_handler"]);
    $enabled_processors = $facet->getProcessors(TRUE);

    $form['facet'] = [
      '#type' => 'container',
      '#title' => $this->t('Facet settings'),
      '#tree' => TRUE,
    ];
    $form['facet']['processors'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    foreach ($all_processors as $processor_id => $processor) {
      if (!($processor instanceof SortProcessorInterface) && $processor->supportsFacet($facet)) {
        $clean_css_id = Html::cleanCssIdentifier($processor_id);
        $default_value = !empty($enabled_processors[$processor_id]);
        $form['facet']['processors'][$processor_id]['status'] = [
          '#type' => 'checkbox',
          '#title' => (string) $processor->getPluginDefinition()['label'],
          '#default_value' => $default_value,
          '#description' => $processor->getDescription(),
          '#attributes' => ['data-processor-id' => $clean_css_id],
        ];

        $form['facet']['processors'][$processor_id]['settings'] = [];
        $processor_form_state = SubformState::createForSubform($form['facet']['processors'][$processor_id]['settings'], $form, $form_state);
        $processor_form = $processor->buildConfigurationForm($form, $processor_form_state, $facet);
        if ($processor_form) {
          $form['facet']['processors'][$processor_id]['settings'] = [
            '#type' => 'details',
            '#title' => $this->t('%processor settings', ['%processor' => (string) $processor->getPluginDefinition()['label']]),
            '#open' => TRUE,
            '#states' => [
              'visible' => [
                ':input[name="options[facet][processors][' . $processor_id . '][status]"]' => ['checked' => TRUE],
              ],
            ],
          ];
          $form['facet']['processors'][$processor_id]['settings'] += $processor_form;
        }
      }
    }

    $form['facet_sort_processors'] = [
      '#type' => 'details',
      '#title' => $this->t('Sort results by'),
    ];
    foreach ($all_processors as $processor_id => $processor) {
      if ($processor instanceof SortProcessorInterface) {
        $clean_css_id = Html::cleanCssIdentifier($processor_id);
        $default_value = !empty($enabled_processors[$processor_id]);
        $form['facet_sort_processors'][$processor_id]['status'] = [
          '#type' => 'checkbox',
          '#title' => (string) $processor->getPluginDefinition()['label'],
          '#default_value' => $default_value,
          '#description' => $processor->getDescription(),
          '#attributes' => ['data-processor-id' => $clean_css_id],
        ];

        $form['facet_sort_processors'][$processor_id]['settings'] = [];
        $processor_form_state = SubformState::createForSubform($form['facet_sort_processors'][$processor_id]['settings'], $form, $form_state);
        $processor_form = $processor->buildConfigurationForm($form, $processor_form_state, $facet);
        if ($processor_form) {
          $form['facet_sort_processors'][$processor_id]['settings'] = [
            '#type' => 'container',
            '#open' => TRUE,
            '#states' => [
              'visible' => [
                ':input[name="options[facet_sort_processors][' . $processor_id . '][status]"]' => ['checked' => TRUE],
              ],
            ],
          ];
          $form['facet_sort_processors'][$processor_id]['settings'] += $processor_form;
        }
      }
    }

    $form['facet']['query_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Operator'),
      '#options' => ['or' => $this->t('OR'), 'and' => $this->t('AND')],
      '#description' => $this->t('AND filters are exclusive and narrow the result set. OR filters are inclusive and widen the result set.'),
      '#default_value' => $facet->getQueryOperator(),
    ];

    $form['facet']['hard_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Hard limit'),
      '#default_value' => $facet->getHardLimit(),
      '#description' => $this->t('Display no more than this number of facet items.<br>*Note: Some search backends will use 0 as "no limit", and some still apply a default limit when hard limit is set to 0. If this affects you, set this to an appropriately-high value or a value that indicates "no limit" to your search backend'),
    ];

    $hierarchy = $facet->getHierarchy();
    $options = array_map(function (HierarchyPluginBase $plugin) {
      return $plugin->getPluginDefinition()['label'];
    }, $facet->getHierarchies());
    $form['facet']['hierarchy'] = [
      '#type' => 'select',
      '#title' => $this->t('Hierarchy type'),
      '#options' => $options,
      '#default_value' => $hierarchy ? $hierarchy['type'] : '',
      '#states' => [
        'visible' => [
          ':input[name="options[facet][processors][hierarchy_processor][status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facet']['expand_hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always expand hierarchy'),
      '#description' => $this->t('Render entire tree, regardless of whether the parents are active or not.'),
      '#default_value' => $facet->getExpandHierarchy(),
      '#states' => [
        'visible' => [
          ':input[name="options[facet][processors][hierarchy_processor][status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facet']['min_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum count'),
      '#default_value' => $facet->getMinCount(),
      '#description' => $this->t('Only display the results if there is this minimum amount of results. The default is "1". A setting "0" might result in a list of all possible facet items, regardless of the actual search query. But the result of a minimum count of "0" is not reliable and may very on the type of the field, the Search API backend and even between different releases or runtime configurations of the backend (for example Solr). Therefore it is highly recommended to avoid any feature that depends on a minimum count of "0".'),
      '#maxlength' => 4,
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['facet']['missing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show missing'),
      '#description' => $this->t('Add a facet item that counts and selects all search results which match the current query but do not belong to any of the facet items.'),
      '#default_value' => $this->options['facet']['missing'],
    ];

    $form['facet']['missing_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label of missing items'),
      '#description' => $this->t('Label of the facet item for which do not belong to any of the regular items.'),
      '#default_value' => $this->options['facet']['missing_label'],
      '#states' => [
        'visible' => [
          ':input[name="options[facet][missing]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facet']['show_numbers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the amount of results'),
      '#default_value' => $this->options["facet"]["show_numbers"],
    ];

    $form['facet']['show_only_one_result'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ensure that only one result can be displayed'),
      '#description' => $this->t('As soon as one option is selected, only that option remains visible until it is deselected.'),
      '#default_value' => $this->options["facet"]["show_only_one_result"],
    ];

    $form['facet']['depends_on_exposed_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Depends on exposed filter'),
      '#description' => $this->t('Only render and apply this facet after the selected exposed filter has an active value. When the selected filter changes in the exposed form, this facet is cleared before the form is submitted.'),
      '#options' => $this->getExposedFilterDependencyOptions(),
      '#default_value' => $this->options["facet"]["depends_on_exposed_filter"] ?? '',
    ];

    $form['weights'] = [
      '#type' => 'details',
      '#title' => $this->t('Processor order'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['weights']['order'] = [
      '#type' => 'container',
    ];

    $stages = $this->processorPluginManager->getProcessingStages();
    $processors_by_stage = [];
    foreach ($stages as $stage => $definition) {
      foreach ($facet->getProcessorsByStage($stage, FALSE) as $processor_id => $processor) {
        if ($processor_id == 'url_processor_handler') {
          continue;
        }
        if ($processor->supportsFacet($facet)) {
          $processors_by_stage[$stage][$processor_id] = $processor;
        }
      }
      unset($processor_id, $processor);
    }
    // Order enabled processors per stage, create all the containers for the
    // different stages.
    foreach ($stages as $stage => $description) {
      $form['weights'][$stage] = [
        '#type' => 'fieldset',
        '#title' => $description['label'],
        '#attributes' => [
          'class' => [
            'search-api-stage-wrapper-' . Html::cleanCssIdentifier($stage),
          ],
        ],
      ];
      $form['weights'][$stage]['order'] = [
        '#type' => 'table',
      ];
      $form['weights'][$stage]['order']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
      ];
    }

    // Fill in the containers previously created with the processors that are
    // enabled on the facet.
    foreach ($processors_by_stage as $stage => $processors) {
      /** @var \Drupal\facets\Processor\ProcessorInterface $processor */
      foreach ($processors as $processor_id => $processor) {
        $weight = $this->options["facet"]["processor_configs"][$processor_id]["weights"][$stage] ?? $processor->getDefaultWeight($stage);
        if ($processor->isHidden()) {
          $form['processors'][$processor_id]['weights'][$stage] = [
            '#type' => 'value',
            '#value' => $weight,
          ];
          continue;
        }
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'draggable';
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'search-api-processor-weight--' . Html::cleanCssIdentifier($processor_id);
        $form['weights'][$stage]['order'][$processor_id]['#weight'] = $weight;
        $form['weights'][$stage]['order'][$processor_id]['label']['#plain_text'] = (string) $processor->getPluginDefinition()['label'];
        $form['weights'][$stage]['order'][$processor_id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for processor %title', ['%title' => (string) $processor->getPluginDefinition()['label']]),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#parents' => ['weights', $processor_id, 'weights', $stage],
          '#attributes' => [
            'class' => [
              'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
            ],
          ],
        ];
      }
    }
    $form['#attached']['library'][] = 'facets_exposed_filters/edit_filter';
    return $form;
  }

  /**
   * Helper function to retrieve the representing facet.
   */
  private function getFacet() {
    $facet = Facet::create([
      'id' => $this->options["field"],
      'field_identifier' => $this->configuration["search_api_field_identifier"],
      'facet_source_id' => 'search_api:views_' . $this->displayHandler->getPluginId() . '__' . $this->view->id() . '__' . $this->view->current_display,
      'query_operator' => $this->options["facet"]["query_operator"] ?? 'or',
      'use_hierarchy' => isset($this->options["facet"]["processor_configs"]["hierarchy_processor"]),
      'expand_hierarchy' => $this->options["facet"]["expand_hierarchy"] ?? FALSE,
      'min_count' => $this->options["facet"]["min_count"] ?? 1,
      'missing' => $this->options["facet"]["missing"] ?? FALSE,
      'missing_label' => $this->options["facet"]["missing_label"] ?? 'others',
      'widget' => '<nowidget>',
      'facet_type' => 'facets_exposed_filter',
    ]);
    $facet->setHardLimit($this->options["facet"]["hard_limit"] ?? 0);
    $facet->setShowOnlyOneResult($this->options["facet"]["show_only_one_result"] ?? FALSE);
    if ($facet->getUseHierarchy()) {
      $facet->setHierarchy($this->options["facet"]["hierarchy"], []);
    }
    if (isset($this->options["facet"]["processor_configs"])) {
      foreach ($this->options["facet"]["processor_configs"] as $processor_id => $processor_settings) {
        $facet->addProcessor([
          'processor_id' => $processor_id,
          'settings' => $processor_settings["settings"] ?? [],
          'weights' => $processor_settings["weights"] ?? [],
        ]);
      }
    }
    return $facet;
  }

  /**
   * Returns the active facet values for the current filter as an array.
   *
   * Since we build the exposed form again after the query is triggered, we need
   * to retrieve the active filters from the request ourself.
   */
  private function getActiveFacetValues() {
    if (!$this->isExposedFilterDependencySatisfied()) {
      return [];
    }

    // Reset button in ajax request. We probably want a better way to detect if
    // this was clicked.
    if (isset($_GET["reset"])) {
      return [];
    }
    $exposed = $this->view->getExposedInput();
    if (!isset($exposed[$this->options["expose"]["identifier"]])) {
      return [];
    }
    $enabled = $exposed[$this->options["expose"]["identifier"]];
    if ($enabled == 'All') {
      return [];
    }
    elseif (!is_array($enabled)) {
      $enabled = [$enabled];
    }
    if ($this->hasExposedRangeKeys($enabled)) {
      return $this->getExposedRangeValues($enabled);
    }

    return $this->normalizeActiveFacetValues(array_values($enabled), $this->getFacet());
  }

  /**
   * Checks whether the configured parent exposed filter has an active value.
   *
   * @param mixed $input
   *   Optional exposed input to inspect.
   *
   * @return bool
   *   TRUE when no dependency is configured or when the dependency is active.
   */
  private function isExposedFilterDependencySatisfied(mixed $input = NULL): bool {
    $dependency_filter_id = $this->getParentExposedFilterId();

    if ($dependency_filter_id === '') {
      return TRUE;
    }

    $dependency_identifier = $this->getExposedFilterIdentifier($dependency_filter_id);

    if ($dependency_identifier === NULL) {
      return TRUE;
    }

    if ($input === NULL) {
      $input = $this->view->getExposedInput();
    }

    if (!is_array($input)) {
      return FALSE;
    }

    $dependency_value = $this->getDependencyInputValue($input, $dependency_identifier);

    if (!$this->hasActiveExposedValue($dependency_value)) {
      return FALSE;
    }

    return $this->isExposedFilterDependencyChainSatisfied($dependency_filter_id, $input, [
      $this->options['id'] ?? '' => TRUE,
    ]);
  }

  /**
   * Returns the configured parent filter handler ID.
   *
   * @return string
   *   The parent filter handler ID or an empty string.
   */
  private function getParentExposedFilterId(): string {
    $filter_id = $this->options["facet"]["depends_on_exposed_filter"] ?? '';

    return is_string($filter_id) ? $filter_id : '';
  }

  /**
   * Checks whether another exposed filter's own dependency chain is active.
   *
   * @param string $filter_id
   *   The filter handler ID to inspect.
   * @param array<string, mixed> $input
   *   The exposed input.
   * @param array<string, bool> $visited
   *   Already inspected filter IDs.
   *
   * @return bool
   *   TRUE when the filter has no inactive parent dependency.
   */
  private function isExposedFilterDependencyChainSatisfied(
    string $filter_id,
    array $input,
    array $visited = [],
  ): bool {
    if (isset($visited[$filter_id])) {
      return TRUE;
    }

    $visited[$filter_id] = TRUE;
    $filters = $this->getFilterHandlers();
    $filter = $filters[$filter_id] ?? NULL;

    if (!$filter instanceof FilterPluginBase) {
      return TRUE;
    }

    $parent_filter_id = $this->getFilterDependencyId($filter);
    if ($parent_filter_id === '') {
      return TRUE;
    }

    $parent_identifier = $this->getExposedFilterIdentifier($parent_filter_id);
    if ($parent_identifier === NULL) {
      return TRUE;
    }

    $parent_dependency_value = $this->getDependencyInputValue(
      $input,
      $parent_identifier,
    );

    if (!$this->hasActiveExposedValue($parent_dependency_value)) {
      return FALSE;
    }

    return $this->isExposedFilterDependencyChainSatisfied($parent_filter_id, $input, $visited);
  }

  /**
   * Builds the Views UI options for exposed filter dependencies.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Options keyed by filter handler ID.
   */
  private function getExposedFilterDependencyOptions(): array {
    $options = [
      '' => $this->t('- None -'),
    ];

    foreach ($this->getFilterHandlers() as $filter_id => $filter) {
      if ($filter_id === ($this->options['id'] ?? NULL)) {
        continue;
      }

      if (!$filter instanceof FilterPluginBase || !$filter->isExposed()) {
        continue;
      }

      $identifier = $this->getExposedFilterIdentifier($filter_id);
      if ($identifier === NULL) {
        continue;
      }

      $label = $filter->options['expose']['label'] ?? $filter->adminLabel();
      $options[$filter_id] = $this->t('@label (@identifier)', [
        '@label' => $label,
        '@identifier' => $identifier,
      ]);
    }

    return $options;
  }

  /**
   * Returns the exposed filter dependency configured on a filter handler.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter handler.
   *
   * @return string
   *   The configured parent filter handler ID or an empty string.
   */
  private function getFilterDependencyId(FilterPluginBase $filter): string {
    $filter_id = $filter->options['facet']['depends_on_exposed_filter'] ?? '';

    return is_string($filter_id) ? $filter_id : '';
  }

  /**
   * Returns all filter handlers for the current display.
   *
   * @return array<string, \Drupal\views\Plugin\views\filter\FilterPluginBase>
   *   Filter handlers keyed by handler ID.
   */
  private function getFilterHandlers(): array {
    if (is_object($this->displayHandler) && method_exists($this->displayHandler, 'getHandlers')) {
      return $this->displayHandler->getHandlers('filter');
    }

    return is_array($this->view->filter ?? NULL) ? $this->view->filter : [];
  }

  /**
   * Returns the exposed identifier for a filter handler.
   *
   * @param string $filter_id
   *   The filter handler ID.
   *
   * @return string|null
   *   The exposed identifier, or NULL when the filter is not exposed.
   */
  private function getExposedFilterIdentifier(string $filter_id): ?string {
    $filters = $this->getFilterHandlers();
    $filter = $filters[$filter_id] ?? NULL;

    if (!$filter instanceof FilterPluginBase || !$filter->isExposed()) {
      return NULL;
    }

    $identifier = $filter->options['expose']['identifier'] ?? NULL;

    if (!is_string($identifier) || $identifier === '') {
      return NULL;
    }

    return $identifier;
  }

  /**
   * Returns an exposed dependency value from Views input or raw exposed input.
   *
   * During the first exposed-form render, Views may omit facet form values
   * because facet options are only available after query execution.
   * The raw exposed input still contains URL values and is the source of truth
   * for deciding whether a dependent facet should apply.
   *
   * @param array<string, mixed> $input
   *   Exposed input provided by Views.
   * @param string $identifier
   *   The exposed filter identifier.
   *
   * @return mixed
   *   The exposed value, or NULL when no value is available.
   */
  private function getDependencyInputValue(array $input, string $identifier): mixed {
    if (array_key_exists($identifier, $input)) {
      return $input[$identifier];
    }

    $exposed_input = $this->view->getExposedInput();
    if (is_array($exposed_input) && array_key_exists($identifier, $exposed_input)) {
      return $exposed_input[$identifier];
    }

    return NULL;
  }

  /**
   * Builds data attributes used by the client-side dependency handler.
   *
   * @return array<string, string>
   *   Data attributes for the exposed form element.
   */
  private function getExposedFilterDependencyAttributes(): array {
    $identifier = $this->options['expose']['identifier'] ?? NULL;

    if (!is_string($identifier) || $identifier === '') {
      return [];
    }

    $attributes = [
      'data-facets-exposed-filter-identifier' => $identifier,
    ];

    $dependency_filter_id = $this->getParentExposedFilterId();
    if ($dependency_filter_id !== '') {
      $dependency_identifier = $this->getExposedFilterIdentifier($dependency_filter_id);
      if ($dependency_identifier !== NULL) {
        $attributes['data-facets-exposed-filter-depends-on'] = $dependency_identifier;
      }
    }

    $dependent_identifiers = $this->getDependentExposedFilterIdentifiers();
    if ($dependent_identifiers !== []) {
      $attributes['data-facets-exposed-filter-dependents'] = implode(' ', $dependent_identifiers);
    }

    if (count($attributes) === 1) {
      return [];
    }

    return $attributes;
  }

  /**
   * Returns exposed identifiers for filters that depend on this filter.
   *
   * @return string[]
   *   The dependent exposed identifiers.
   */
  private function getDependentExposedFilterIdentifiers(): array {
    $current_filter_id = $this->options['id'] ?? NULL;

    if (!is_string($current_filter_id) || $current_filter_id === '') {
      return [];
    }

    $identifiers = [];
    foreach ($this->getFilterHandlers() as $filter_id => $filter) {
      if (!$filter instanceof FilterPluginBase || $filter_id === $current_filter_id) {
        continue;
      }

      $dependency_filter_id = $this->getFilterDependencyId($filter);
      if ($dependency_filter_id !== $current_filter_id) {
        continue;
      }

      $identifier = $this->getExposedFilterIdentifier($filter_id);
      if ($identifier !== NULL) {
        $identifiers[] = $identifier;
      }
    }

    return array_values(array_unique($identifiers));
  }

  /**
   * Checks whether an exposed filter value should count as active.
   *
   * @param mixed $value
   *   The exposed filter value.
   *
   * @return bool
   *   TRUE when the value is not empty or the Views all option.
   */
  private function hasActiveExposedValue(mixed $value): bool {
    if (is_array($value)) {
      foreach ($value as $child_value) {
        if ($this->hasActiveExposedValue($child_value)) {
          return TRUE;
        }
      }

      return FALSE;
    }

    if ($value === NULL || $value === FALSE) {
      return FALSE;
    }

    $value = trim((string) $value);

    return $value !== '' && $value !== 'All';
  }

  /**
   * Filters results for facets limited to a single visible active option.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being rendered.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The facet results.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   The filtered results.
   */
  private function applyShowOnlyOneResult(FacetInterface $facet, array $results): array {
    if (!$facet->getShowOnlyOneResult() || $facet->getActiveItems() === []) {
      return $results;
    }

    $active_values = array_map('strval', array_values($facet->getActiveItems()));

    return $this->filterResultsByActiveValues($facet, $results, $active_values);
  }

  /**
   * Filters final exposed options down to the submitted values.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being rendered.
   * @param array<string, string> $options
   *   The final exposed options keyed by submitted value.
   *
   * @return array<string, string>
   *   The filtered options.
   */
  private function filterOptionsByActiveValues(FacetInterface $facet, array $options): array {
    if (!$facet->getShowOnlyOneResult() || $facet->getActiveItems() === []) {
      return $options;
    }

    $active_values = array_fill_keys(array_map('strval', array_values($facet->getActiveItems())), TRUE);

    return array_filter(
      $options,
      static fn (string $value, string $key): bool => isset($active_values[$key]),
      ARRAY_FILTER_USE_BOTH,
    );
  }

  /**
   * Filters facet results down to the selected exposed option values.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being rendered.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The facet results.
   * @param string[] $active_values
   *   The active exposed option values.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   The filtered results.
   */
  private function filterResultsByActiveValues(FacetInterface $facet, array $results, array $active_values): array {
    $filtered_results = [];

    foreach ($results as $key => $result) {
      $filtered_children = $this->filterResultsByActiveValues($facet, $result->getChildren(), $active_values);

      if ($filtered_children !== []) {
        $result->setChildren($filtered_children);
        $filtered_results[$key] = $result;
        continue;
      }

      $option_value = $this->getExposedOptionValue($facet, $result);
      if (in_array($option_value, $active_values, TRUE)) {
        $filtered_results[$key] = $result;
      }
    }

    return $filtered_results;
  }

  /**
   * Synchronizes processor-updated active items back into exposed state.
   *
   * Processors may clear or normalize active items in pre-query. The exposed
   * filter integration must propagate that updated state to the view and the
   * current request so AJAX rebuilds do not restore stale selections.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The processed facet.
   * @param bool $checkbox_shape
   *   When TRUE, multiple values are written back as `value => value` pairs for
   *   checkbox rendering. Otherwise, they remain a plain list for query
   *   execution.
   */
  private function syncFacetActiveItemsToExposedState(FacetInterface $facet, bool $checkbox_shape = FALSE): void {
    $identifier = $this->options["expose"]["identifier"];

    if (!is_string($identifier) || $identifier === '') {
      return;
    }

    $stored_value = $this->getStoredActiveFacetValues($facet, $checkbox_shape);

    $exposed_input = $this->view->getExposedInput();
    $exposed_input = is_array($exposed_input) ? $exposed_input : [];

    if ($stored_value === NULL || $stored_value === []) {
      unset($exposed_input[$identifier]);
    }
    else {
      $exposed_input[$identifier] = $stored_value;
    }

    $this->view->setExposedInput($exposed_input);

    if (isset($this->view->exposed_raw_input) && is_array($this->view->exposed_raw_input)) {
      if ($stored_value === NULL || $stored_value === []) {
        unset($this->view->exposed_raw_input[$identifier]);
      }
      else {
        $this->view->exposed_raw_input[$identifier] = $stored_value;
      }
    }

    if (!$checkbox_shape) {
      return;
    }

    $request = $this->requestStack->getCurrentRequest();

    if (!$request) {
      return;
    }

    if ($stored_value === NULL || $stored_value === []) {
      $request->query->remove($identifier);
      $request->request->remove($identifier);
      unset($_GET[$identifier], $_POST[$identifier], $_REQUEST[$identifier]);
    }
    else {
      $request->query->set($identifier, $stored_value);
      $request->request->set($identifier, $stored_value);
      $_GET[$identifier] = $stored_value;
      $_POST[$identifier] = $stored_value;
      $_REQUEST[$identifier] = $stored_value;
    }
  }

  /**
   * Returns active facet items in the shape expected by exposed filters.
   *
   * Multiple checkbox filters must stay keyed by option value so subsequent
   * query-string serialization produces `field[value]=value` instead of
   * indexed arrays like `field[0]=value`.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The processed facet.
   * @param bool $checkbox_shape
   *   When TRUE, multiple values are returned in Drupal checkbox shape
   *   (`value => value`). Otherwise, multiple values are returned as a plain
   *   list, which is what the facet query execution expects.
   *
   * @return array|string|null
   *   The active items in the requested shape, the first scalar value for
   *   single filters, or NULL when there is no active value.
   */
  private function getStoredActiveFacetValues(FacetInterface $facet, bool $checkbox_shape = FALSE): array|string|null {
    $active_items = $facet->getActiveItems();

    if ($this->hasExposedRangeKeys($active_items)) {
      return $this->getExposedRangeValues($active_items);
    }

    $active_values = array_values($active_items);

    if (empty($this->options["expose"]["multiple"])) {
      return $active_values[0] ?? NULL;
    }

    if (!$checkbox_shape) {
      return $active_values;
    }

    $stored_values = [];
    foreach ($active_values as $active_value) {
      $stored_values[(string) $active_value] = $active_value;
    }

    return $stored_values;
  }

  /**
   * Checks whether values are in exposed range shape.
   *
   * @param array<mixed> $values
   *   The values.
   *
   * @return bool
   *   TRUE when the values contain min/max keys.
   */
  private function hasExposedRangeKeys(array $values): bool {
    if (array_key_exists('min', $values) || array_key_exists('max', $values)) {
      return TRUE;
    }

    if (count($values) !== 1) {
      return FALSE;
    }

    $range = reset($values);

    return is_array($range) && array_key_exists(0, $range) && array_key_exists(1, $range);
  }

  /**
   * Returns only the supported exposed range keys.
   *
   * @param array<mixed> $values
   *   The values.
   *
   * @return array<string, mixed>
   *   The exposed min/max values.
   */
  private function getExposedRangeValues(array $values): array {
    if (array_key_exists('min', $values) || array_key_exists('max', $values)) {
      return array_intersect_key($values, [
        'min' => TRUE,
        'max' => TRUE,
      ]);
    }

    $range = reset($values);

    if (!is_array($range) || !array_key_exists(0, $range) || !array_key_exists(1, $range)) {
      return [];
    }

    return [
      'min' => $range[0],
      'max' => $range[1],
    ];
  }

  /**
   * Normalizes active facet values for hierarchical exposed filters.
   *
   * Also handles range slider keys by delegating to getExposedRangeValues().
   *
   * @param array<mixed> $active_items
   *   The active items.
   * @param \Drupal\facets\FacetInterface|null $facet
   *   The current facet.
   *
   * @return array<mixed>
   *   Normalized active items.
   */
  private function normalizeActiveFacetValues(array $active_items, ?FacetInterface $facet = NULL): array {
    if ($this->hasExposedRangeKeys($active_items)) {
      return $this->getExposedRangeValues($active_items);
    }

    $active_values = array_values($active_items);

    if (
      !$facet instanceof FacetInterface
      || !$facet->getUseHierarchy()
      || $facet->getKeepHierarchyParentsActive()
      || count($active_values) < 2
    ) {
      return $active_values;
    }

    $hierarchy = $facet->getHierarchyInstance();
    $normalized_values = [];

    foreach ($active_values as $active_value) {
      $is_parent_of_active_value = FALSE;

      foreach ($active_values as $candidate_value) {
        if ($candidate_value === $active_value) {
          continue;
        }

        if (in_array((string) $active_value, $hierarchy->getParentIds((string) $candidate_value), TRUE)) {
          $is_parent_of_active_value = TRUE;
          break;
        }
      }

      if (!$is_parent_of_active_value) {
        $normalized_values[(string) $active_value] = (string) $active_value;
      }
    }

    // Keep query behavior aligned with classic facet links. This does not
    // restore the parent trail when an exposed child value is unchecked; that
    // would require additional form state beyond this normalization.
    return array_values($normalized_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitExtraOptionsForm($form, FormStateInterface $form_state) {
    $options = $form_state->getValue('options');
    $weights = $form_state->getValue('weights');
    $processor_configs = [];
    // Remove disabled processors.
    foreach ($options["facet"]["processors"] as $processor_id => $processor_data) {
      if ($processor_data["status"] == 1) {
        $processor_configs[$processor_id] = [
          'processor_id' => $processor_id,
          'settings' => $processor_data["settings"] ?? [],
        ];
      }
    }
    foreach ($options["facet_sort_processors"] as $processor_id => $processor_data) {
      if ($processor_data["status"] == 1) {
        $processor_configs[$processor_id] = [
          'processor_id' => $processor_id,
          'settings' => $processor_data["settings"] ?? [],
        ];
      }
    }
    foreach ($processor_configs as $processor_id => $processor_config) {
      foreach ($weights[$processor_id]["weights"] as $stage => $weight) {
        $processor_configs[$processor_id]['weights'][$stage] = (int) $weight;
      }
    }
    unset($options["weights"]);
    // Values are merged and stored in processor_configs.
    unset($options["facet"]["processors"]);
    unset($options["facet_sort_processors"]);
    $options["facet"]["processor_configs"] = $processor_configs;

    // Better exposed filters checks for a specific key to determine if
    // hierarchy should be enabled. Lets ensure it is set.
    if (isset($options["facet"]["processor_configs"]["hierarchy_processor"])) {
      $options["hierarchy"] = TRUE;
    }
    else {
      $options["hierarchy"] = FALSE;
      unset($options["facet"]["hierarchy"]);
      unset($options["facet"]["expand_hierarchy"]);
    }

    $options["facet"]["min_count"] = (int) $options["facet"]["min_count"];
    $options["facet"]["missing"] = (bool) $options["facet"]["missing"];
    $options["facet"]["missing_label"] = $options["facet"]["missing_label"];
    $options["facet"]["show_numbers"] = (bool) $options["facet"]["show_numbers"];
    $options["facet"]["show_only_one_result"] = (bool) $options["facet"]["show_only_one_result"];
    $options["facet"]["depends_on_exposed_filter"] = is_string($options["facet"]["depends_on_exposed_filter"] ?? NULL)
      ? $options["facet"]["depends_on_exposed_filter"]
      : '';

    $form_state->setValue('options', $options);
    parent::submitExtraOptionsForm($form, $form_state);
  }

  /**
   * Sort the facet results, and recurse to children to do the same.
   *
   * @param \Drupal\facets\Processor\SortProcessorInterface[] $active_sort_processors
   *   An array of sort processors.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   An array of results.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   A sorted array of results.
   */
  protected function sortFacetResults(array $active_sort_processors, array $results) {
    uasort($results, function ($a, $b) use ($active_sort_processors) {
      $return = 0;
      foreach ($active_sort_processors as $sort_processor) {
        if ($return = $sort_processor->sortResults($a, $b)) {
          if ($sort_processor->getConfiguration()['sort'] == 'DESC') {
            $return *= -1;
          }
          break;
        }
      }
      return $return;
    });
    // Loop over the results and see if they have any children, if they do, fire
    // a request to this same method again with the children.
    foreach ($results as &$result) {
      if (!empty($result->getChildren())) {
        $children = $this->sortFacetResults($active_sort_processors, $result->getChildren());
        $result->setChildren($children);
      }
    }
    return $results;
  }

}
