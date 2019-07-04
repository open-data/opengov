<?php

namespace Drupal\feeds\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;

/**
 * Provides a form for mapping settings.
 */
class MappingForm extends FormBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The mappings for this feed type.
   *
   * @var array
   */
  protected $mappings;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feeds_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FeedTypeInterface $feeds_feed_type = NULL) {
    $feed_type = $this->feedType = $feeds_feed_type;
    $this->targets = $targets = $feed_type->getMappingTargets();

    // Denormalize targets.
    $this->sourceOptions = [];
    foreach ($feed_type->getMappingSources() as $key => $info) {
      $this->sourceOptions[$key] = $info['label'];
    }
    $this->sourceOptions = $this->sortOptions($this->sourceOptions);
    $this->sourceOptions = [
      '__new' => $this->t('New source...'),
      '----' => '----',
    ] + $this->sourceOptions;

    $target_options = [];
    foreach ($targets as $key => $target) {
      $target_options[$key] = $target->getLabel();
    }
    $target_options = $this->sortOptions($target_options);

    if ($form_state->getValues()) {
      $this->processFormState($form, $form_state);

      $triggering_element = $form_state->getTriggeringElement() + ['#op' => ''];

      switch ($triggering_element['#op']) {
        case 'cancel':
        case 'configure':
          // These don't need a configuration message.
          break;

        default:
          $this->messenger()->addWarning($this->t('Your changes will not be saved until you click the <em>Save</em> button at the bottom of the page.'));
          break;
      }
    }

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="feeds-mapping-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'feeds/feeds';

    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Target'),
        $this->t('Summary'),
        $this->t('Configure'),
        $this->t('Unique'),
        $this->t('Remove'),
      ],
      '#sticky' => TRUE,
    ];

    foreach ($feed_type->getMappings() as $delta => $mapping) {
      $table[$delta] = $this->buildRow($form, $form_state, $mapping, $delta);
    }

    $table['add']['source']['#markup'] = '';

    $table['add']['target'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a target'),
      '#title_display' => 'invisible',
      '#options' => $target_options,
      '#empty_option' => $this->t('- Select a target -'),
      '#parents' => ['add_target'],
      '#default_value' => NULL,
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'none',
        'progress' => 'none',
      ],
    ];

    $table['add']['summary']['#markup'] = '';
    $table['add']['configure']['#markup'] = '';
    $table['add']['unique']['#markup'] = '';
    $table['add']['remove']['#markup'] = '';

    $form['mappings'] = $table;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Allow plugins to hook into the mapping form.
    foreach ($feed_type->getPlugins() as $plugin) {
      if ($plugin instanceof MappingPluginFormInterface) {
        $plugin->mappingFormAlter($form, $form_state);
      }
    }

    return $form;
  }

  /**
   * Builds a single mapping row.
   *
   * @param array $form
   *   The complete mapping form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   * @param array $mapping
   *   A single configured mapper, which is expected to consist of the
   *   following:
   *   - map
   *     An array of target subfield => source field.
   *   - target
   *     The name of the target plugin.
   *   - unique
   *     (optional) An array of subfield => enabled as unique.
   *   - settings
   *     (optional) An array of settings for the target.
   * @param int $delta
   *   The index number of the mapping.
   *
   * @return array
   *   The form structure for a single mapping row.
   */
  protected function buildRow(array $form, FormStateInterface $form_state, array $mapping, $delta) {
    $ajax_delta = -1;
    $triggering_element = (array) $form_state->getTriggeringElement() + ['#op' => ''];
    if ($triggering_element['#op'] === 'configure') {
      $ajax_delta = $form_state->getTriggeringElement()['#delta'];
    }

    $row = ['#attributes' => ['class' => ['draggable', 'tabledrag-leaf']]];
    $row['map'] = ['#type' => 'container'];
    $row['targets'] = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    foreach ($mapping['map'] as $column => $source) {
      if (!$this->targets[$mapping['target']]->hasProperty($column)) {
        unset($mapping['map'][$column]);
        continue;
      }
      $row['map'][$column] = [
        'select' => [
          '#type' => 'select',
          '#options' => $this->sourceOptions,
          '#default_value' => $source,
          '#empty_option' => $this->t('- Select a source -'),
          '#attributes' => ['class' => ['feeds-table-select-list']],
        ],
        '__new' => [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="mappings[' . $delta . '][map][' . $column . '][select]"]' => ['value' => '__new'],
            ],
          ],
          'value' => [
            '#type' => 'textfield',
            '#states' => [
              'visible' => [
                ':input[name="mappings[' . $delta . '][map][' . $column . '][select]"]' => ['value' => '__new'],
              ],
            ],
          ],
          'machine_name' => [
            '#type' => 'machine_name',
            '#machine_name' => [
              'exists' => [$this->feedType, 'customSourceExists'],
              'source' => ['mappings', $delta, 'map', $column, '__new', 'value'],
              'standalone' => TRUE,
              'label' => '',
            ],
            '#default_value' => '',
            '#required' => FALSE,
            '#disabled' => '',
          ],
        ],
      ];

      $label = Html::escape($this->targets[$mapping['target']]->getLabel());

      if (count($mapping['map']) > 1) {
        $label .= ': ' . $this->targets[$mapping['target']]->getPropertyLabel($column);
      }
      else {
        $label .= ': ' . $this->targets[$mapping['target']]->getDescription();
      }
      $row['targets']['#items'][] = $label;
    }

    $default_button = [
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'feeds-mapping-form-ajax-wrapper',
        'effect' => 'fade',
        'progress' => 'none',
      ],
      '#delta' => $delta,
    ];

    if ($plugin = $this->feedType->getTargetPlugin($delta)) {

      if ($plugin instanceof ConfigurableTargetInterface) {
        if ($delta == $ajax_delta) {
          $row['settings'] = $plugin->buildConfigurationForm([], $form_state);
          $row['settings']['actions'] = [
            '#type' => 'actions',
            'save_settings' => $default_button + [
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#value' => $this->t('Update'),
              '#op' => 'update',
              '#name' => 'target-save-' . $delta,
            ],
            'cancel_settings' => $default_button + [
              '#type' => 'submit',
              '#value' => $this->t('Cancel'),
              '#op' => 'cancel',
              '#name' => 'target-cancel-' . $delta,
              '#limit_validation_errors' => [[]],
            ],
          ];
          $row['configure']['#markup'] = '';
          $row['#attributes']['class'][] = 'feeds-mapping-settings-editing';
        }
        else {
          $row['settings'] = [
            '#type' => 'item',
            '#markup' => $plugin->getSummary(),
            '#parents' => ['config_summary', $delta],
          ];
          $row['configure'] = $default_button + [
            '#type' => 'image_button',
            '#op' => 'configure',
            '#name' => 'target-settings-' . $delta,
            '#src' => 'core/misc/icons/787878/cog.svg',
          ];
        }
      }
      else {
        $row['settings']['#markup'] = '';
        $row['configure']['#markup'] = '';
      }
    }
    else {
      $row['settings']['#markup'] = '';
      $row['configure']['#markup'] = '';
    }

    $mappings = $this->feedType->getMappings();

    foreach ($mapping['map'] as $column => $source) {
      if ($this->targets[$mapping['target']]->isUnique($column)) {
        $row['unique'][$column] = [
          '#title' => $this->t('Unique'),
          '#type' => 'checkbox',
          '#default_value' => !empty($mappings[$delta]['unique'][$column]),
          '#title_display' => 'invisible',
        ];
      }
      else {
        $row['unique']['#markup'] = '';
      }
    }

    if ($delta != $ajax_delta) {
      $row['remove'] = $default_button + [
        '#title' => $this->t('Remove'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title_display' => 'invisible',
        '#parents' => ['remove_mappings', $delta],
        '#remove' => TRUE,
      ];
    }
    else {
      $row['remove']['#markup'] = '';
    }

    return $row;
  }

  /**
   * Processes the form state, populating the mappings on the feed type.
   *
   * @param array $form
   *   The complete mapping form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function processFormState(array $form, FormStateInterface $form_state) {
    // Process any plugin configuration.
    $triggering_element = $form_state->getTriggeringElement() + ['#op' => ''];
    if ($triggering_element['#op'] === 'update') {
      $this->feedType->getTargetPlugin($triggering_element['#delta'])->submitConfigurationForm($form, $form_state);
    }

    $mappings = $this->feedType->getMappings();
    foreach (array_filter((array) $form_state->getValue('mappings', [])) as $delta => $mapping) {
      foreach ($mapping['map'] as $column => $value) {
        if ($value['select'] == '__new') {
          // Add a new source.
          $this->feedType->addCustomSource($value['__new']['machine_name'], [
            'label' => $value['__new']['value'],
          ] + $value['__new']);
          $mappings[$delta]['map'][$column] = $value['__new']['machine_name'];
        }
        else {
          $mappings[$delta]['map'][$column] = $value['select'];
        }
      }
      if (isset($mapping['unique'])) {
        $mappings[$delta]['unique'] = array_filter($mapping['unique']);
      }
    }
    $this->feedType->setMappings($mappings);

    // Remove any mappings.
    foreach (array_keys(array_filter($form_state->getValue('remove_mappings', []))) as $delta) {
      $this->feedType->removeMapping($delta);
    }

    // Add any targets.
    if ($new_target = $form_state->getValue('add_target')) {
      $map = array_fill_keys($this->targets[$new_target]->getProperties(), '');
      $this->feedType->addMapping([
        'target' => $new_target,
        'map' => $map,
      ]);
    }

    // Allow the #default_value of 'add_target' to be reset.
    $input =& $form_state->getUserInput();
    unset($input['add_target']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form_state->getTriggeringElement()['#delta'])) {
      $delta = $form_state->getTriggeringElement()['#delta'];
      $this->feedType->getTargetPlugin($delta)->validateConfigurationForm($form, $form_state);
      $form_state->setRebuild();
    }
    else {
      // Allow plugins to validate the mapping form.
      foreach ($this->feedType->getPlugins() as $plugin) {
        if ($plugin instanceof MappingPluginFormInterface) {
          $plugin->mappingFormValidate($form, $form_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->processFormState($form, $form_state);

    // Allow plugins to hook into the mapping form.
    foreach ($this->feedType->getPlugins() as $plugin) {
      if ($plugin instanceof MappingPluginFormInterface) {
        $plugin->mappingFormSubmit($form, $form_state);
      }
    }

    $this->feedType->save();
  }

  /**
   * Builds an options list from mapping sources or targets.
   *
   * @param array $options
   *   The options to sort.
   *
   * @return array
   *   The sorted options.
   */
  protected function sortOptions(array $options) {
    $result = [];
    foreach ($options as $k => $v) {
      if (is_array($v) && !empty($v['label'])) {
        $result[$k] = $v['label'];
      }
      elseif (is_array($v)) {
        $result[$k] = $k;
      }
      else {
        $result[$k] = $v;
      }
    }
    asort($result);

    return $result;
  }

  /**
   * Callback for ajax requests.
   *
   * @return array
   *   The form element to return.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Page title callback.
   *
   * @return string
   *   The title of the mapping page.
   */
  public function mappingTitle(FeedTypeInterface $feeds_feed_type) {
    return $this->t('Mappings @label', ['@label' => $feeds_feed_type->label()]);
  }

}
