<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Exception;
use RuntimeException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Plugin\Type\ConfigurablePluginBase;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\Encoder\EncoderInterface;

/**
 * The Feeds extensible parser.
 */
abstract class ParserBase extends ConfigurablePluginBase implements ParserInterface, MappingPluginFormInterface {

  /**
   * The messenger, for compatibility with Drupal 8.5.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $feedsExMessenger;

  /**
   * The class used as the text encoder.
   *
   * @var string
   */
  protected $encoderClass = '\Drupal\feeds_ex\Encoder\TextEncoder';

  /**
   * The encoder used to convert encodings.
   *
   * @var \Drupal\feeds_ex\Encoder\EncoderInterface
   */
  protected $encoder;

  /**
   * Returns rows to be parsed.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   Source information.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result returned by the fetcher.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   *
   * @return array|Traversable
   *   Some iterable that returns rows.
   */
  abstract protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state);

  /**
   * Executes a single source expression.
   *
   * @param string $machine_name
   *   The source machine name being executed.
   * @param string $expression
   *   The expression to execute.
   * @param mixed $row
   *   The row to execute on.
   *
   * @return scalar|[]scalar
   *   Either a scalar, or a list of scalars. If null, the value will be
   *   ignored.
   */
  abstract protected function executeSourceExpression($machine_name, $expression, $row);

  /**
   * Validates an expression.
   *
   * @param string &$expression
   *   The expression to validate.
   *
   * @return string|null
   *   Return the error string, or null if validation was passed.
   */
  abstract protected function validateExpression(&$expression);

  /**
   * Returns the errors after parsing.
   *
   * @return array
   *   A structured array array with keys:
   *   - message: The error message.
   *   - variables: The variables for the message.
   *   - severity: The severity of the message.
   *
   * @see watchdog()
   */
  abstract protected function getErrors();

  /**
   * Allows subclasses to prepare for parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The result of the fetching stage.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function setUp(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
  }

  /**
   * Allows subclasses to cleanup after parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed we are parsing for.
   * @param \Drupal\feeds\Result\ParserResultInterface $parser_result
   *   The result of parsing.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $parser_result, StateInterface $state) {
  }

  /**
   * Starts internal error handling.
   *
   * Subclasses can override this to being error handling.
   */
  protected function startErrorHandling() {
  }

  /**
   * Stops internal error handling.
   *
   * Subclasses can override this to end error handling.
   */
  protected function stopErrorHandling() {
  }

  /**
   * Loads the necessary library.
   *
   * Subclasses can override this to load the necessary library. It will be
   * called automatically.
   *
   * @throws RuntimeException
   *   Thrown if the library does not exist.
   */
  protected function loadLibrary() {
  }

  /**
   * Returns whether or not this parser uses a context query.
   *
   * Sub-classes can return false here if they don't require a user-configured
   * context query.
   *
   * @return bool
   *   True if the parser uses a context query and false if not.
   */
  protected function hasConfigurableContext() {
    return TRUE;
  }

  /**
   * Returns the label for single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if the source has a special name. Null otherwise.
   */
  protected function configSourceLabel() {
    return NULL;
  }

  /**
   * Returns the list of table headers.
   *
   * @return array
   *   A list of header names keyed by the form keys.
   */
  protected function configFormTableHeader() {
    return [];
  }

  /**
   * Returns a form element for a specific column.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $values
   *   The individual source item values.
   * @param string $column
   *   The name of the column.
   * @param string $machine_name
   *   The machine name of the source.
   *
   * @return array
   *   A single form element.
   */
  protected function configFormTableColumn(FormStateInterface $form_state, array $values, $column, $machine_name) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $this->loadLibrary();
    $this->startErrorHandling();
    $result = new ParserResult();
    // @todo Set link?
    // $fetcher_config = $feed->getConfigurationFor($feed->importer->fetcher);
    // $result->link = is_string($fetcher_config['source']) ? $fetcher_config['source'] : '';

    try {
      $this->setUp($feed, $fetcher_result, $state);
      $this->parseItems($feed, $fetcher_result, $result, $state);
      $this->cleanUp($feed, $result, $state);
    }
    catch (EmptyFeedException $e) {
      // The feed is empty.
      $this->getMessenger()->addMessage($this->t('The feed is empty.'), 'warning', FALSE);
    }
    catch (Exception $exception) {
      // Do nothing. Store for later.
    }

    // Display errors.
    $errors = $this->getErrors();
    $this->printErrors($errors, $this->configuration['display_errors'] ? RfcLogLevel::DEBUG : RfcLogLevel::ERROR);

    $this->stopErrorHandling();

    if (isset($exception)) {
      throw $exception;
    }

    return $result;
  }

  /**
   * Performs the actual parsing.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed source.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   * @param \Drupal\feeds\Result\ParserResultInterface $result
   *   The parser result object to populate.
   * @param \Drupal\feeds\StateInterface $state
   *   The state object.
   */
  protected function parseItems(FeedInterface $feed, FetcherResultInterface $fetcher_result, ParserResultInterface $result, StateInterface $state) {
    $expressions = $this->prepareExpressions();
    $variable_map = $this->prepareVariables($expressions);

    foreach ($this->executeContext($feed, $fetcher_result, $state) as $row) {
      if ($item = $this->executeSources($row, $expressions, $variable_map)) {
        $result->addItem($item);
      }
    }
  }

  /**
   * Prepares the expressions for parsing.
   *
   * At this point we just remove empty expressions.
   *
   * @return array
   *   A map of machine name to expression.
   */
  protected function prepareExpressions() {
    $expressions = [];
    foreach ($this->configuration['sources'] as $machine_name => $source) {
      if (strlen($source['value'])) {
        $expressions[$machine_name] = $source['value'];
      }
    }

    return $expressions;
  }

  /**
   * Prepares the variable map used to substitution.
   *
   * @param array $expressions
   *   The expressions being parsed.
   *
   * @return array
   *   A map of machine name to variable name.
   */
  protected function prepareVariables(array $expressions) {
    $variable_map = [];
    foreach ($expressions as $machine_name => $expression) {
      $variable_map[$machine_name] = '$' . $machine_name;
    }
    return $variable_map;
  }

  /**
   * Executes the source expressions.
   *
   * @param mixed $row
   *   A single item returned from the context expression.
   * @param array $expressions
   *   A map of machine name to expression.
   * @param array $variable_map
   *   A map of machine name to varible name.
   *
   * @return array
   *   The fully-parsed item array.
   */
  protected function executeSources($row, array $expressions, array $variable_map) {
    $item = new DynamicItem();
    $variables = [];

    foreach ($expressions as $machine_name => $expression) {
      // Variable substitution.
      $expression = strtr($expression, $variables);

      $result = $this->executeSourceExpression($machine_name, $expression, $row);

      if (!empty($this->configuration['sources'][$machine_name]['debug'])) {
        $this->debug($result, $machine_name);
      }

      if ($result === NULL) {
        $variables[$variable_map[$machine_name]] = '';
        continue;
      }

      $item->set($machine_name, $result);
      $variables[$variable_map[$machine_name]] = is_array($result) ? reset($result) : $result;
    }

    return $item;
  }

  /**
   * Prints errors to the screen.
   *
   * @param array $errors
   *   A list of errors as returned by stopErrorHandling().
   * @param int $severity
   *   (optional) Limit to only errors of the specified severity. Defaults to
   *   RfcLogLevel::ERROR.
   *
   * @see watchdog()
   */
  protected function printErrors(array $errors, $severity = RfcLogLevel::ERROR) {
    foreach ($errors as $error) {
      if ($error['severity'] > $severity) {
        continue;
      }
      $this->getMessenger()->addMessage($this->t($error['message'], $error['variables']), $error['severity'] <= RfcLogLevel::ERROR ? 'error' : 'warning', FALSE);
    }
  }

  /**
   * Prepares the raw string for parsing.
   *
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   *
   * @return string
   *   The prepared raw string.
   */
  protected function prepareRaw(FetcherResultInterface $fetcher_result) {
    $raw = trim($this->getEncoder()->convertEncoding($fetcher_result->getRaw()));

    if (!strlen($raw)) {
      throw new EmptyFeedException();
    }

    return $raw;
  }

  /**
   * Renders our debug messages into a list.
   *
   * @param mixed $data
   *   The result of an expression. Either a scalar or a list of scalars.
   * @param string $machine_name
   *   The source key that produced this query.
   */
  protected function debug($data, $machine_name) {
    $name = $machine_name;
    if ($this->configuration['sources'][$machine_name]['name']) {
      $name = $this->configuration['sources'][$machine_name]['name'];
    }

    $output = '<strong>' . $name . ':</strong>';
    $data = is_array($data) ? $data : [$data];
    foreach ($data as $key => $value) {
      $data[$key] = SafeMarkup::checkPlain($value);
    }
    $output .= _theme('item_list', ['items' => $data]);
    $this->getMessenger()->addMessage($output);
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return $this->configuration['sources'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sources' => [],
      'context' => [
        'value' => '',
      ],
      'display_errors' => FALSE,
      'source_encoding' => ['auto'],
      'debug_mode' => FALSE,
      'line_limit' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Preserve some configuration.
    $config = array_merge([
      'context' => $this->getConfiguration('context'),
      'sources' => $this->getConfiguration('sources'),
    ], $form_state->getValues());

    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state) {
    if ($this->hasConfigurableContext()) {
      $form['context'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Context'),
        '#default_value' => $this->configuration['context']['value'],
        '#description' => $this->t('The base query to run.'),
        '#size' => 50,
        '#required' => TRUE,
        '#maxlength' => 1024,
        '#weight' => -50,
      ];
    }

    // Override the label for adding new sources, so it is more clear what the
    // source value represents.
    $source_label = $this->configSourceLabel();
    if ($source_label) {
      foreach (Element::children($form['mappings']) as $i) {
        if (!isset($form['mappings'][$i]['map'])) {
          continue;
        }
        foreach (Element::children($form['mappings'][$i]['map']) as $subtarget) {
          $form['mappings'][$i]['map'][$subtarget]['select']['#options']['__new'] = $this->t('New @label...', [
            '@label' => $source_label,
          ]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state) {
    // Validate context.
    if ($this->hasConfigurableContext()) {
      if ($message = $this->validateExpression($form_state->getValue('context'))) {
        $form_state->setErrorByName('context', $message);
      }
    }

    // Validate new sources.
    $mappings = $form_state->getValue('mappings');
    foreach ($mappings as $i => $mapping) {
      foreach ($mapping['map'] as $subtarget => $map) {
        if ($map['select'] == '__new' && isset($map['__new']['value'])) {
          if ($message = $this->validateExpression($map['__new']['value'])) {
            $form_state->setErrorByName("mappings][$i][map][$subtarget][__new][value", $message);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state) {
    $config = [];

    // Set context.
    $config['context'] = [
      'value' => $form_state->getValue('context'),
    ];

    // Set sources.
    // @todo refactor to let parsers use custom sources directly.
    $config['sources'] = [];
    $mappings = $form_state->getValue('mappings');
    foreach ($mappings as $i => $mapping) {
      foreach ($mapping['map'] as $subtarget => $map) {
        if (empty($map['select'])) {
          continue;
        }
        if ($map['select'] == '__new') {
          $name = $map['__new']['machine_name'];
        }
        else {
          $name = $map['select'];
        }

        $source = $this->feedType->getCustomSource($name);
        if ($source) {
          unset($source['machine_name']);
          $config['sources'][$name] = $source;
        }
      }
    }

    $this->setConfiguration($config);
  }

  /**
   * Builds configuration form for the parser settings.
   *
   * @todo The code below is still D7 code and does not work in D8 yet. Also,
   * it's likely that most of the code below is no longer needed as the parser
   * UI is planned to be implemented in a completely different way.
   *
   * @see https://www.drupal.org/node/2917924
   */
  public function _buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = [
      '#tree' => TRUE,
      '#theme' => 'feeds_ex_configuration_table',
      '#prefix' => '<div id="feeds-ex-configuration-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['sources'] = [
      '#id' => 'feeds-ex-source-table',
      '#attributes' => [
        'class' => ['feeds-ex-source-table'],
      ],
    ];

    $max_weight = 0;
    foreach ($this->configuration['sources'] as $machine_name => $source) {
      $form['sources'][$machine_name]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#title_display' => 'invisible',
        '#default_value' => $source['name'],
        '#size' => 20,
      ];
      $form['sources'][$machine_name]['machine_name'] = [
        '#title' => $this->t('Machine name'),
        '#title_display' => 'invisible',
        '#markup' => $machine_name,
      ];
      $form['sources'][$machine_name]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#title_display' => 'invisible',
        '#default_value' => $source['value'],
        '#size' => 50,
        '#maxlength' => 1024,
      ];

      foreach ($this->configFormTableHeader() as $column => $name) {
        $form['sources'][$machine_name][$column] = $this->configFormTableColumn($form_state, $source, $column, $machine_name);
      }

      $form['sources'][$machine_name]['debug'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Debug'),
        '#title_display' => 'invisible',
        '#default_value' => $source['debug'],
      ];
      $form['sources'][$machine_name]['remove'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Remove'),
        '#title_display' => 'invisible',
      ];
      $form['sources'][$machine_name]['weight'] = [
        '#type' => 'textfield',
        '#default_value' => $source['weight'],
        '#size' => 3,
        '#attributes' => ['class' => ['feeds-ex-source-weight']],
      ];
      $max_weight = $source['weight'];
    }

    $form['add']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add new source'),
      '#id' => 'edit-sources-add-name',
      '#description' => $this->t('Name'),
      '#size' => 20,
    ];
    $form['add']['machine_name'] = [
      '#title' => $this->t('Machine name'),
      '#title_display' => 'invisible',
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => 'feeds_ex_source_exists',
        'source' => ['add', 'name'],
        'standalone' => TRUE,
        'label' => '',
      ],
      '#field_prefix' => '<span dir="ltr">',
      '#field_suffix' => '</span>&lrm;',
      '#feeds_importer' => $this->id,
      '#required' => FALSE,
      '#maxlength' => 32,
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
    ];
    $form['add']['value'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Value'),
      '#title' => '&nbsp;',
      '#size' => 50,
      '#maxlength' => 1024,
    ];
    foreach ($this->configFormTableHeader() as $column => $name) {
      $form['add'][$column] = $this->configFormTableColumn($form_state, [], $column, '');
    }
    $form['add']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#title_display' => 'invisible',
    ];
    $form['add']['weight'] = [
      '#type' => 'textfield',
      '#default_value' => ++$max_weight,
      '#size' => 3,
      '#attributes' => ['class' => ['feeds-ex-source-weight']],
    ];
    $form['display_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display errors'),
      '#description' => $this->t('Display all error messages after parsing. Fatal errors will always be displayed.'),
      '#default_value' => $this->configuration['display_errors'],
    ];
    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode'),
      '#description' => $this->t('Displays the configuration form on the feed source page to ease figuring out the expressions. Any values entered on that page will be saved here.'),
      '#default_value' => $this->configuration['debug_mode'],
    ];

    $form = $this->getEncoder()->buildConfigurationForm($form, $form_state);

    $form['#attached']['drupal_add_tabledrag'][] = [
      'feeds-ex-source-table',
      'order',
      'sibling',
      'feeds-ex-source-weight',
    ];
    $form['#attached']['css'][] = drupal_get_path('module', 'feeds_ex') . '/feeds_ex.css';
    $form['#header'] = $this->getFormHeader();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate(&$values) {
    // Throwing an exception during validation shows a nasty error to users.
    try {
      $this->loadLibrary();
    }
    catch (RuntimeException $e) {
      $this->getMessenger()->addMessage($e->getMessage(), 'error', FALSE);
      return;
    }

    // @todo We should do this in Feeds automatically.
    $values += $this->defaultConfiguration();

    // Remove sources.
    foreach ($values['sources'] as $machine_name => $source) {
      if (!empty($source['remove'])) {
        unset($values['sources'][$machine_name]);
      }
    }

    // Add new source.
    if (strlen($values['add']['machine_name']) && strlen($values['add']['name'])) {
      if ($message = $this->validateExpression($values['add']['value'])) {
        form_set_error('add][value', $message);
      }
      else {
        $values['sources'][$values['add']['machine_name']] = $values['add'];
      }
    }

    // Rebuild sources to keep the configuration values clean.
    $columns = $this->getFormHeader();
    unset($columns['remove'], $columns['machine_name']);
    $columns = array_keys($columns);

    foreach ($values['sources'] as $machine_name => $source) {
      $new_value = [];
      foreach ($columns as $column) {
        $new_value[$column] = $source[$column];
      }
      $values['sources'][$machine_name] = $new_value;
    }

    // Sort by weight.
    uasort($values['sources'], 'ctools_plugin_sort');

    // Let the encoder do its thing.
    $this->getEncoder()->configFormValidate($values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasConfigForm() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceDefaults() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    if (!$this->hasSourceConfig()) {
      return [];
    }

    $form = $this->buildConfigurationForm($form, $form_state);
    $form['add']['machine_name']['#machine_name']['source'] = [
      'feeds',
      get_class($this),
      'add',
      'name',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceFormValidate(&$source_config) {
    $this->configFormValidate($source_config);
  }

  /**
   * {@inheritdoc}
   */
  public function sourceSave(FeedInterface $feed) {
    $config = $feed->getConfigurationFor($this);
    $feed->setConfigFor($this, []);

    if ($this->hasSourceConfig() && $config) {
      $this->setConfig($config);
      $this->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasSourceConfig() {
    return !empty($this->configuration['debug_mode']);
  }

  /**
   * Returns the configuration form table header.
   *
   * @return array
   *   The header array.
   */
  protected function getFormHeader() {
    $header = [
      'name' => $this->t('Name'),
      'machine_name' => $this->t('Machine name'),
      'value' => $this->t('Value'),
    ];
    $header += $this->configFormTableHeader();
    $header += [
      'debug' => $this->t('Debug'),
      'remove' => $this->t('Remove'),
      'weight' => $this->t('Weight'),
    ];

    return $header;
  }

  /**
   * Sets the encoder.
   *
   * @param \Drupal\feeds_ex\Encoder\EncoderInterface $encoder
   *   The encoder.
   *
   * @return $this
   *   The parser object.
   */
  public function setEncoder(EncoderInterface $encoder) {
    $this->encoder = $encoder;
    return $this;
  }

  /**
   * Returns the encoder.
   *
   * @return \Drupal\feeds_ex\Encoder\EncoderInterface
   *   The encoder object.
   */
  public function getEncoder() {
    if (!isset($this->encoder)) {
      $class = $this->encoderClass;
      $this->encoder = new $class($this->configuration['source_encoding']);
    }
    return $this->encoder;
  }

  /**
   * Sets the messenger.
   *
   * For compatibility with both Drupal 8.5 and Drupal 8.6.
   * Basically only useful for automated tests.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function setFeedsExMessenger(MessengerInterface $messenger) {
    if (method_exists($this, 'setMessenger')) {
      $this->setMessenger($messenger);
    }
    else {
      $this->feedsExMessenger = $messenger;
    }
  }

  /**
   * Gets the messenger.
   *
   * For compatibility with both Drupal 8.5 and Drupal 8.6.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger.
   */
  public function getMessenger() {
    if (method_exists($this, 'messenger')) {
      return $this->messenger();
    }
    if (isset($this->feedsExMessenger)) {
      return $this->feedsExMessenger;
    }
    return \Drupal::messenger();
  }

}
