<?php

namespace Drupal\webform_options_limit\Plugin\WebformHandler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Element\WebformEntityTrait;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElementEntityOptionsInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_options_limit\Plugin\WebformOptionsLimitHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform options limit handler.
 *
 * @WebformHandler(
 *   id = "options_limit",
 *   label = @Translation("Options limit"),
 *   category = @Translation("Options"),
 *   description = @Translation("Define options submission limit."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class OptionsLimitWebformHandler extends WebformHandlerBase implements WebformOptionsLimitHandlerInterface {

  use WebformAjaxElementTrait;

  /**
   * Default option value.
   */
  const DEFAULT_LIMIT = '_default_';

  /**
   * Option limit single remaining.
   */
  const LIMIT_STATUS_SINGLE = 'single';

  /**
   * Option limit multiple remaining.
   */
  const LIMIT_STATUS_MULTIPLE = 'multiple';

  /**
   * Option limit none remaining.
   */
  const LIMIT_STATUS_NONE = 'none';

  /**
   * Option limit unlimited.
   */
  const LIMIT_STATUS_UNLIMITED = 'unlimited';

  /**
   * Option limit eror.
   */
  const LIMIT_STATUS_ERROR = 'error';

  /**
   * Option limit action disable.
   */
  const LIMIT_ACTION_DISABLE = 'disable';

  /**
   * Option limit action remove.
   */
  const LIMIT_ACTION_REMOVE = 'remove';

  /**
   * Option limit action none.
   */
  const LIMIT_ACTION_NONE = 'none';

  /**
   * Option message label.
   */
  const MESSAGE_DISPLAY_LABEL = 'label';

  /**
   * Option message none.
   */
  const MESSAGE_DISPLAY_DESCRIPTION = 'description';

  /**
   * Option message none.
   */
  const MESSAGE_DISPLAY_NONE = 'none';

  /**
   * The element (cached) label.
   *
   * @var string
   */
  protected $elementLabel;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, Connection $database, WebformTokenManagerInterface $token_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->database = $database;
    $this->tokenManager = $token_manager;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('database'),
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnonymousSubmissionTracking() {
    return $this->configuration['limit_user'];
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $source_entity = NULL) {
    $this->sourceEntity = $source_entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    if ($this->sourceEntity) {
      return $this->sourceEntity;
    }
    elseif ($this->getWebformSubmission()) {
      return $this->getWebformSubmission()->getSourceEntity();
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'element_key' => '',
      'limits' => [],
      'limit_reached_message' => '@name is not available',
      'limit_source_entity' => TRUE,
      'limit_user' => FALSE,
      'option_none_action' => 'disable',
      'option_message_display' => 'label',
      'option_multiple_message' => '[@remaining remaining]',
      'option_single_message' => '[@remaining remaining]',
      'option_unlimited_message' => '[Unlimited]',
      'option_none_message' => '[@remaining remaining]',
      'option_error_message' => '@name: @label is unavailable.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];

    $element = $this->getWebform()->getElement($settings['element_key']);
    if ($element) {
      $webform_element = $this->elementManager->getElementInstance($element);
      $t_args = [
        '@title' => $webform_element->getAdminLabel($element),
        '@type' => $webform_element->getPluginLabel(),
      ];
      $settings['element_key'] = $this->t('@title (@type)', $t_args);
    }
    elseif (empty($settings['element_key'])) {
      $settings['element_key'] = [
        '#type' => 'link',
        '#title' => $this->t('Please add a new options elements.'),
        '#url' => $this->getWebform()->toUrl('edit-form'),
      ];
    }
    else {
      $settings['element_key'] = [
        '#markup' => $this->t("'@element_key' is missing.", ['@element_key' => $settings['element_key']]),
        '#prefix' => '<b class="color-error">',
        '#suffix' => '</b>',
      ];
    }

    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);

    // Get elements with options.
    $elements_with_options = $this->getElementsWithOptions();

    // Make sure that there an options element available.
    if (empty($elements_with_options)) {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => [
          'message' => [
            '#markup' => $this->t('No options elements are available.'),
            '#prefix' => '<p>',
            '#suffix' => '</p>',
          ],
          'link' => [
            '#type' => 'link',
            '#title' => $this->t('Please add a new options elements.'),
            '#url' => $this->getWebform()->toUrl('edit-form'),
            '#prefix' => '<p>',
            '#suffix' => '</p>',
          ],
        ],
      ];
      return $form;
    }

    // Element settings.
    $form['element_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Element settings'),
      '#open' => TRUE,
    ];
    $form['element_settings']['element_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Element'),
      '#options' => $this->getElementsWithOptions(),
      '#default_value' => $this->configuration['element_key'],
      '#required' => TRUE,
      '#empty_option' => (empty($this->configuration['element_key'])) ? $this->t('- Select -') : NULL,
    ];
    $form['element_settings']['options_container'] = [];
    $element = $this->getElement();
    if ($element) {
      $webform_element = $this->getWebformElement();
      $element_options = $this->getElementOptions() + [
        static::DEFAULT_LIMIT => $this->t('Default (Used when option has no limit)'),
      ];
      $t_args = [
        '@title' => $webform_element->getAdminLabel($element),
        '@type' => $this->t('option'),
      ];
      $form['element_settings']['options_container']['limits'] = [
        '#type' => 'webform_mapping',
        '#title' => $this->t('@title @type limits', $t_args),
        '#description_display' => 'before',
        '#source' => $element_options,
        '#source__title' => $this->t('Options'),
        '#destination__type' => 'number',
        '#destination__min' => 1,
        '#destination__title' => $this->t('Limit'),
        '#destination__description' => NULL,
        '#default_value' => $this->configuration['limits'],
      ];
    }
    else {
      $form['element_settings']['options_container']['limits'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }
    $this->buildAjaxElement(
      'webform-options-limit',
      $form['element_settings']['options_container'],
      $form['element_settings']['element_key']
    );

    // Limit settings.
    $form['limit_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Limit settings'),
    ];
    $form['limit_settings']['limit_reached_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit reached message'),
      '#description' => $this->t('This message will be displayed when all option limits are reached.')
        . '<br/><br/>'
        . $this->t('Leave blank to hide this message.'),
      '#default_value' => $this->configuration['limit_reached_message'],
    ];
    $form['limit_settings']['limit_source_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply options limit to each source entity'),
      '#description' => $this->t('If checked, options limit will be applied to this webform and each source entity individually.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['limit_source_entity'],
    ];
    $form['limit_settings']['limit_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply options limit to per user'),
      '#description' => $this->t("If checked, options limit will be applied per submission for authenticated and anonymous users. Anonymous user options limits are only tracked by the user's browser sessions. Per user limits work best for authenticated users."),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['limit_user'],
    ];
    $form['limit_settings']['limit_user_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Anonymous user options limits are only tracked by the user\'s browser session. It is recommended that options limit to per user only be used on forms restricted to authenticated users.'),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#states' => [
        'visible' => [
          ':input[name="settings[limit_user]"]' => ['checked' => TRUE],
        ]
      ]
    ];
    // Option settings.
    $form['option_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Option settings'),
    ];
    $form['option_settings']['option_none_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Option limit reached behavior'),
      '#options' => [
        static::LIMIT_ACTION_DISABLE => $this->t('Disable the option'),
        static::LIMIT_ACTION_REMOVE => $this->t('Remove the option'),
        static::LIMIT_ACTION_NONE => $this->t('Do not alter the option'),
      ],
      '#default_value' => $this->configuration['option_none_action'],
    ];
    $form['option_settings']['option_message_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Option message display'),
      '#options' => [
        static::MESSAGE_DISPLAY_LABEL => $this->t("Append message to the option's text"),
        static::MESSAGE_DISPLAY_DESCRIPTION => $this->t("Append message to the option's description"),
        static::MESSAGE_DISPLAY_NONE => $this->t("Do not display a message"),
      ],
      '#default_value' => $this->configuration['option_message_display'],
    ];
    $form['option_settings']['option_message'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="settings[option_message_display]"]' => ['!value' => 'none'],
        ],
      ],
    ];
    $form['option_settings']['option_message']['option_multiple_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option multiple remaining message'),
      '#description' => $this->t('The message is displayed when multiple options are available.')
        . '<br/><br/>'
        . $this->t('Leave blank to hide this message.'),
      '#default_value' => $this->configuration['option_multiple_message'],
    ];
    $form['option_settings']['option_message']['option_single_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option one remaining message'),
      '#description' => $this->t('The message is displayed when only one option is available.')
        . '<br/><br/>'
        . $this->t('Leave blank to hide this message.'),
      '#default_value' => $this->configuration['option_single_message'],
    ];
    $form['option_settings']['option_message']['option_none_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option none remaining message'),
      '#description' => $this->t('The message is displayed when no options are available.')
        . '<br/><br/>'
        . $this->t('Leave blank to hide this message.'),
      '#default_value' => $this->configuration['option_none_message'],
    ];
    $form['option_settings']['option_message']['option_unlimited_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option unlimited message'),
      '#description' => $this->t('The message is displayed an option has not limits.')
        . '<br/><br/>'
        . $this->t('Leave blank to hide this message.'),
      '#default_value' => $this->configuration['option_unlimited_message'],
    ];
    $form['option_settings']['option_error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option validation error message'),
      '#description' => $this->t('The message is displayed when an element with an option limit validation error is submitted.'),
      '#default_value' => $this->configuration['option_error_message'],
      '#required' => TRUE,
    ];

    // Placeholder help.
    $form['placeholder_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Placeholder help'),
      'description' => [
        '#markup' => $this->t('The following placeholders can be used:'),
      ],
      'items' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('@limit - The total number of submissions allowed for the option.'),
          $this->t('@total - The current number of submissions for the option.'),
          $this->t('@remaining - The remaining number of submissions for the option.'),
          $this->t("@label - The element option's label."),
          $this->t("@name - The element's title."),
        ],
      ],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);

    foreach ($this->configuration['limits'] as $key => $value) {
      $this->configuration['limits'][$key] = (int) $value;
    }

    // Clear cached element label.
    // @see \Drupal\webform_options_limit\Plugin\WebformHandler\OptionsLimitWebformHandler::getElementLabel
    $this->elementLabel = NULL;
  }

  /****************************************************************************/
  // Alter element methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function alterElement(array &$element, FormStateInterface $form_state, array $context) {
    if (empty($element['#webform_key'])
      || $element['#webform_key'] !== $this->configuration['element_key']) {
      return;
    }

    // Set webform submission for form object.
    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();
    $this->setWebformSubmission($webform_submission);

    // Get limits, disabled, and (form) operation.
    $limits = $this->getLimits();
    $disabled = $this->getDisabled($limits);
    $operation = $form_object->getOperation();

    // Cleanup default value.
    $this->setElementDefaultValue($element, $limits, $disabled, $operation);

    // Alter element options label.
    $options =& $element['#options'];
    $this->alterElementOptions($options, $limits);

    // Disable element options.
    if ($disabled) {
      switch ($this->configuration['option_none_action']) {
        case static::LIMIT_ACTION_DISABLE:
          $this->disableElementOptions($element, $disabled);
          break;

        case static::LIMIT_ACTION_REMOVE:
          $this->removeElementOptions($element, $disabled);
          break;
      }
    }

    // Display limit reached message.
    $this->setLimitReachedMessage($element, $limits, $disabled);

    // Add validate callback.
    $element['#element_validate'][] = [get_called_class(), 'validateWebformOptionsLimit'];
    $element['#webform_option_limit_handler_id'] = $this->getHandlerId();
  }

  /**
   * Set and cleanup the element's default value.
   *
   * @param array $element
   *   A webform element with options limit.
   * @param array $limits
   *   A webform element's option limits.
   * @param array $disabled
   *   A webform element's disabled options.
   * @param string $operation
   *   The form's current operation.
   */
  protected function setElementDefaultValue(array &$element, array $limits, array $disabled, $operation) {
    $webform_element = $this->getWebformElement();
    $has_multiple_values = $webform_element->hasMultipleValues($element);
    // Make sure the test default value is an enabled option.
    if ($operation === 'test') {
      $test_values = array_keys($disabled ? array_diff_key($limits, $disabled) : $limits);
      if ($test_values) {
        $test_value = $test_values[array_rand($test_values)];
        $element['#default_value'] = ($has_multiple_values) ? [$test_value] : $test_value;
      }
      else {
        $element['#default_value'] = ($has_multiple_values) ? [] : NULL;
      }
    }
    // Cleanup default values.
    elseif (!empty($element['#default_value'])) {
      $default_value = $element['#default_value'];
      if ($has_multiple_values) {
        $element['#default_value'] = array_values(array_diff($default_value, $disabled));
      }
      else {
        if (isset($disabled[$default_value])) {
          $element['#default_value'] = ($has_multiple_values) ? [] : NULL;
        }
      }
    }
  }

  /**
   * Set element's limit reached message.
   *
   * @param array $element
   *   A webform element with options limit.
   * @param array $limits
   *   A webform element's option limits.
   * @param array $disabled
   *   A webform element's disabled options.
   */
  protected function setLimitReachedMessage(array &$element, array $limits, array $disabled) {
    if (count($limits) !== count($disabled)) {
      return;
    }
    if (empty($this->configuration['limit_reached_message'])) {
    }
    $args = ['@name' => $this->getElementLabel()];
    $element['#description'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => new FormattableMarkup($this->configuration['limit_reached_message'], $args),
    ];
  }

  /**
   * Alter an element's options recursively.
   *
   * @param array $options
   *   An array of options.
   * @param array $limits
   *   A webform element's option limits.
   */
  protected function alterElementOptions(array &$options, array $limits) {
    foreach ($options as $option_value => $option_text) {
      if (is_array($option_text)) {
        $this->alterElementOptions($option_text, $limits);
      }
      elseif (isset($limits[$option_value])) {
        $options[$option_value] = $this->getLimitLabel(
          $option_text,
          $limits[$option_value]
        );
      }
    }
  }

  /**
   * Disable element options.
   *
   * @param array $element
   *   A webform element with options limit.
   * @param array $disabled
   *   An array of disabled options.
   */
  protected function disableElementOptions(array &$element, array $disabled) {
    $webform_element = $this->getWebformElement();
    if ($webform_element->hasProperty('options__properties')) {
      // Set element options disabled properties.
      foreach ($disabled as $disabled_option) {
        $element['#options__properties'][$disabled_option] = [
          '#disabled' => TRUE,
        ];
      }
    }
    else {
      // Set select menu disabled attribute.
      // @see Drupal.behaviors.webformSelectOptionsDisabled
      // @see webform.element.select.js
      $element['#attributes']['data-webform-select-options-disabled'] = implode(',', $disabled);
    }
  }

  /**
   * Remove element options.
   *
   * @param array $element
   *   A webform element with options limit.
   * @param array $disabled
   *   An array of disabled options.
   */
  protected function removeElementOptions(array &$element, array $disabled) {
    $options =& $element['#options'];
    $this->removeElementOptionsRecursive($options, $disabled);
  }

  /**
   * Remove element options recursively.
   *
   * @param array $options
   *   An array options (and optgroups).
   * @param array $disabled
   *   An array of disabled options.
   */
  protected function removeElementOptionsRecursive(array &$options, array $disabled) {
    foreach ($options as $option_value => &$option_text) {
      if (is_array($option_text)) {
        $this->removeElementOptionsRecursive($option_text, $disabled);
        if (empty($option_text)) {
          unset($options[$option_value]);
        }
      }
      elseif (isset($disabled[$option_value])) {
        unset($options[$option_value]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Must invalidate tags.
    Cache::invalidateTags(['webform:' . $this->getWebform()->id()]);
  }

  /****************************************************************************/
  // Summary method.
  // @see \Drupal\webform_options_limit\Controller\WebformOptionsLimitController
  /****************************************************************************/

  /**
   * Build summary table.
   *
   * @return array
   *   A renderable containing the options limit summary table.
   */
  public function buildSummaryTable() {
    $element = $this->getElement();
    if (!$element) {
      return [];
    }

    if ($this->configuration['limit_user']) {
      return [];
    }

    $webform_element = $this->getWebformElement();

    $rows = [];
    $limits = $this->getLimits();
    foreach ($limits as $limit) {
      if ($limit['limit']) {
        $percentage = number_format(($limit['total'] / $limit['limit']) * 100) . '% ';
        $progress = [
          '#type' => 'html_tag',
          '#tag' => 'progress',
          '#attributes' => [
            'max' => $limit['limit'],
            'value' => $limit['total'],
          ],
        ];
      }
      else {
        $percentage = '';
        $progress = [];
      }

      $rows[] = [
        ['data' => $limit['label'], 'style' => 'font-weight: bold'],
        ['data' => $limit['limit'] ?: '∞', 'style' => 'text-align: right'],
        ['data' => $limit['limit'] ? $limit['remaining'] : '∞', 'style' => 'text-align: right'],
        ['data' => $limit['total'], 'style' => 'text-align: right'],
        ['data' => $progress, 'style' => 'text-align: center'],
        ['data' => $percentage, 'style' => 'text-align: right'],
      ];
    }

    return [
      'title' => [
        '#markup' => $webform_element->getLabel($element),
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          '',
          ['data' => $this->t('Limit'), 'style' => 'text-align: right'],
          ['data' => $this->t('Remaining'), 'style' => 'text-align: right', 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Total'), 'style' => 'text-align: right', 'class' => [RESPONSIVE_PRIORITY_LOW]],
          ['data' => $this->t('Progress'), 'style' => 'text-align: center', 'class' => [RESPONSIVE_PRIORITY_LOW]],
          '',
        ],
        '#rows' => $rows,
      ],
    ];
  }

  /****************************************************************************/
  // Validation methods.
  /****************************************************************************/

  /**
   * Validate webform options limit.
   */
  public static function validateWebformOptionsLimit(&$element, FormStateInterface $form_state, &$complete_form) {
    // Skip if element is not visible.
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    $webform = $form_object->getWebform();

    /** @var \Drupal\webform_options_limit\Plugin\WebformHandler\OptionsLimitWebformHandler $handler */
    $handler = $webform->getHandler($element['#webform_option_limit_handler_id']);
    $handler->validateElement($element, $form_state);
  }

  /**
   * Validate a webform element with options limit.
   *
   * @param array $element
   *   A webform element with options limit.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @internal
   *   This method should only called by
   *   OptionsLimitWebformHandler::validateWebformOptionsLimit.
   */
  public function validateElement(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();
    $this->setWebformSubmission($webform_submission);

    $element_key = $this->configuration['element_key'];

    // Get casting as array to support multiple options.
    $original_values = (array) $webform_submission->getElementOriginalData($element_key);
    $values = (array) $form_state->getValue($element_key);
    if (empty($values) || $values === ['']) {
      return;
    }

    $limits = $this->getLimits($values);
    foreach ($limits as $value => $limit) {
      // Do not apply option limit to any previously selected option value.
      if (in_array($value, $original_values)) {
        continue;
      }
      if ($limit['status'] === static::LIMIT_STATUS_NONE) {
        $message = $this->getOptionStatusMessage(static::LIMIT_STATUS_ERROR, $limit);
        $form_state->setError($element, $message);
      }
    }
  }

  /****************************************************************************/
  // Element methods.
  /****************************************************************************/

  /**
   * Get selected element.
   *
   * @return array
   *   Selected element.
   */
  protected function getElement() {
    return $this->getWebform()->getElement($this->configuration['element_key']);
  }

  /**
   * Get selected webform element plugin.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface|null
   *   A webform element plugin instance.
   */
  protected function getWebformElement() {
    $element = $this->getElement();
    return ($element) ? $this->elementManager->getElementInstance($element) : NULL;
  }

  /**
   * Get selected webform element label.
   *
   * @return string
   *   A webform element label.
   */
  protected function getElementLabel() {
    if (isset($this->elementLabel)) {
      return $this->elementLabel;
    }

    $element = $this->getElement();
    $webform_element = $this->getWebformElement();
    $this->elementLabel = $webform_element->getLabel($element);
    return $this->elementLabel;
  }

  /**
   * Get key/value array of webform elements with options.
   *
   * @return array
   *   A key/value array of webform elements with options.
   */
  protected function getElementsWithOptions() {
    $webform = $this->getWebform();
    $elements = $webform->getElementsInitializedAndFlattened();

    $options = [];
    foreach ($elements as $element_key => $element) {
      $webform_element = $this->elementManager->getElementInstance($element);

      $is_options_element = ($webform_element->hasProperty('options')
        && strpos($webform_element->getPluginLabel(), 'tableselect') === FALSE);

      $is_entity_options_element = ($webform_element instanceof WebformElementEntityOptionsInterface);

      if ($is_options_element || $is_entity_options_element) {
        $webform_key = $element['#webform_key'];
        $t_args = [
          '@title' => $webform_element->getAdminLabel($element),
          '@type' => $webform_element->getPluginLabel(),
        ];
        $options[$webform_key] = $this->t('@title (@type)', $t_args);
      }
    }

    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof WebformOptionsLimitHandlerInterface
        && $handler->getHandlerId() !== $this->getHandlerId()) {
        $configuration = $handler->getConfiguration();
        unset($options[$configuration['settings']['element_key']]);
      }
    }

    return $options;
  }

  /**
   * Get selected element's options.
   *
   * @return array
   *   A key/value array of options.
   */
  protected function getElementOptions() {
    $element = $this->getElement();

    // Set entity options.
    $webform_element = $this->getWebformElement();
    if ($webform_element instanceof WebformElementEntityOptionsInterface) {
      WebformEntityTrait::setOptions($element);
    }

    return ($element) ? OptGroup::flattenOptions($element['#options']) : [];
  }

  /****************************************************************************/
  // Limits methods.
  /****************************************************************************/

  /**
   * Get an associative array of options limits.
   *
   * @param array $values
   *   Optional array of values to get options limit.
   *
   * @return array
   *   An associative array of options limits keyed by option value and
   *   including the option's limit, total, remaining, and status.
   */
  protected function getLimits(array $values = []) {
    $default_limit = isset($this->configuration['limits'][static::DEFAULT_LIMIT])
      ? $this->configuration['limits'][static::DEFAULT_LIMIT]
      : NULL;

    $totals = $this->getTotals($values);

    $options = $this->getElementOptions();
    if ($values) {
      $options = array_intersect_key($options, array_combine($values, $values));
    }

    $limits = [];
    foreach ($options as $option_key => $option_label) {
      $limit = (isset($this->configuration['limits'][$option_key]))
        ? $this->configuration['limits'][$option_key]
        : $default_limit;

      $total = (isset($totals[$option_key])) ? $totals[$option_key] : 0;

      $remaining = ($limit) ? $limit - $total : NULL;

      if (empty($limit)) {
        $status = static::LIMIT_STATUS_UNLIMITED;
      }
      elseif ($remaining <= 0) {
        $status = static::LIMIT_STATUS_NONE;
      }
      elseif ($remaining === 1) {
        $status = static::LIMIT_STATUS_SINGLE;
      }
      else {
        $status = static::LIMIT_STATUS_MULTIPLE;
      }

      $limits[$option_key] = [
        'label' => $option_label,
        'limit' => $limit,
        'total' => $total,
        'remaining' => $remaining,
        'status' => $status,
      ];
    }
    return $limits;
  }

  /**
   * Get value array of disabled options.
   *
   * @param array $limits
   *   An associative array of options limits.
   *
   * @return array
   *   A value array of disabled options.
   */
  protected function getDisabled(array $limits) {
    $element_key = $this->configuration['element_key'];
    $webform_submission = $this->getWebformSubmission();
    $element_values = (array) $webform_submission->getElementOriginalData($element_key) ?: [];
    $disabled = [];
    foreach ($limits as $option_value => $limit) {
      if ($element_values && in_array($option_value, $element_values)) {
        continue;
      }
      if ($limit['status'] === static::LIMIT_STATUS_NONE) {
        $disabled[$option_value] = $option_value;
      }
    }
    return $disabled;
  }

  /**
   * Get options submission totals for the current webform and source entity.
   *
   * @param array $values
   *   Optional array of values to get totals.
   *
   * @return array
   *   A key/value array of options totals.
   */
  protected function getTotals(array $values = []) {
    $webform = $this->getWebform();

    /** @var \Drupal\Core\Database\StatementInterface $result */
    $query = $this->database->select('webform_submission', 's');
    $query->join('webform_submission_data', 'sd', 's.sid = sd.sid');
    $query->fields('sd', ['value']);
    $query->addExpression('COUNT(value)', 'total');
    $query->condition('sd.name', $this->configuration['element_key']);
    $query->condition('sd.webform_id', $webform->id());
    $query->groupBy('value');

    // Limit by option values.
    if ($values) {
      $query->condition('sd.value', $values, 'IN');
    }

    // Limit by source entity.
    if ($this->configuration['limit_source_entity']) {
      $source_entity = $this->getSourceEntity();
      if ($source_entity) {
        $query->condition('s.entity_type', $source_entity->getEntityTypeId());
        $query->condition('s.entity_id', $source_entity->id());
      }
      else {
        $query->isNull('s.entity_type');
        $query->isNull('s.entity_id');
      }
    }

    // Limit by authenticated or anonymous user.
    if ($this->configuration['limit_user']) {
      $account = \Drupal::currentUser();
      if ($account->isAuthenticated()) {
        $query->condition('s.uid', $account->id());
      }
      else {
        $sids = $this->submissionStorage->getAnonymousSubmissionIds($account);
        if ($sids) {
          $query->condition('s.sid', $sids, 'IN');
          $query->condition('s.uid', 0);
        }
        else {
          return [];
        }
      }
    }

    return $query->execute()->fetchAllKeyed();
  }

  /****************************************************************************/
  // Labels and messages methods.
  /****************************************************************************/

  /**
   * Get options status message.
   *
   * @param string $type
   *   Type of message.
   * @param array $limit
   *   Associative array containing limit, total, remaining, and label.
   *
   * @return \Drupal\Component\Render\FormattableMarkup|string
   *   A option status message.
   */
  protected function getOptionStatusMessage($type, array $limit) {
    $message = $this->configuration['option_' . $type . '_message'];
    if (!$message) {
      return '';
    }

    return new FormattableMarkup($message, [
      '@name' => $this->getElementLabel(),
      '@label' => $limit['label'],
      '@limit' => $limit['limit'],
      '@total' => $limit['total'],
      '@remaining' => $limit['remaining'],
    ]);
  }

  /**
   * Get option limit label.
   *
   * @param string $label
   *   An option's label.
   * @param array $limit
   *   The option's limit information.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   An option's limit label.
   */
  protected function getLimitLabel($label, array $limit) {
    $message_display = $this->configuration['option_message_display'];
    if ($message_display === static::MESSAGE_DISPLAY_NONE) {
      return $label;
    }

    $message = $this->getOptionStatusMessage($limit['status'], $limit);
    if (!$message) {
      return $label;
    }

    switch ($message_display) {
      case static::MESSAGE_DISPLAY_LABEL:
        $t_args = ['@label' => $label, '@message' => $message];
        return $this->t('@label @message', $t_args);

      case static::MESSAGE_DISPLAY_DESCRIPTION:
        return $label
          . (strpos($label, WebformOptionsHelper::DESCRIPTION_DELIMITER) === FALSE ? WebformOptionsHelper::DESCRIPTION_DELIMITER : '')
          . $message;
    }
  }

}
