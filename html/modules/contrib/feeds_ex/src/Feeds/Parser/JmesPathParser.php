<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use RuntimeException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds_ex\JmesRuntimeFactory;
use Drupal\feeds_ex\JmesRuntimeFactoryInterface;
use Drupal\feeds_ex\Utility\JsonUtility;
use JmesPath\SyntaxErrorException;

/**
 * Defines a JSON parser using JMESPath.
 *
 * @FeedsParser(
 *   id = "jmespath",
 *   title = @Translation("JSON JMESPath"),
 *   description = @Translation("Parse JSON with JMESPath."),
 *   arguments = {"@feeds_ex.json_utility"}
 * )
 */
class JmesPathParser extends JsonParserBase {

  /**
   * The JMESPath parser.
   *
   * This is an object with an __invoke() method.
   *
   * @var object
   *
   * @todo add interface so checking for an object with an __invoke() method
   * becomes explicit?
   */
  protected $runtime;

  /**
   * A factory to generate JMESPath runtime objects.
   *
   * @var \Drupal\feeds_ex\JmesRuntimeFactoryInterface
   */
  protected $runtimeFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, JsonUtility $utility) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $utility);

    // Set default factory.
    $this->runtimeFactory = new JmesRuntimeFactory();
  }

  /**
   * Sets the factory to use for creating JMESPath Runtime objects.
   *
   * This is useful in unit tests.
   *
   * @param \Drupal\feeds_ex\JmesRuntimeFactoryInterface $factory
   *   The factory to use.
   */
  public function setRuntimeFactory(JmesRuntimeFactoryInterface $factory) {
    $this->runtimeFactory = $factory;
  }

  /**
   * Returns data from the input array that matches a JMESPath expression.
   *
   * @param string $expression
   *   JMESPath expression to evaluate.
   * @param mixed $data
   *   JSON-like data to search.
   *
   * @return mixed|null
   *   Returns the matching data or null.
   */
  protected function search($expression, $data) {
    if (!isset($this->runtime)) {
      $this->runtime = $this->runtimeFactory->createRuntime();
    }

    // Stupid PHP.
    $runtime = $this->runtime;

    return $runtime($expression, $data);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $raw = $this->prepareRaw($fetcher_result);
    $parsed = $this->utility->decodeJsonArray($raw);
    $parsed = $this->search($this->configuration['context']['value'], $parsed);
    if (!is_array($parsed)) {
      throw new RuntimeException($this->t('The context expression must return an object or array.'));
    }

    if (!$state->total) {
      $state->total = count($parsed);
    }

    // @todo Consider using array slice syntax when it is supported.
    $start = (int) $state->pointer;
    $state->pointer = $start + $this->configuration['line_limit'];
    return array_slice($parsed, $start, $this->configuration['line_limit']);
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUp(FeedInterface $feed, ParserResultInterface $result, StateInterface $state) {
    // @todo Verify if this is necessary. Not sure if the runtime keeps a
    // reference to the input data.
    unset($this->runtime);
    // Calculate progress.
    $state->progress($state->total, $state->pointer);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = $this->search($expression, $row);

    if (is_scalar($result)) {
      return $result;
    }

    // Return a single value if there's only one value.
    return count($result) === 1 ? reset($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
    if (!strlen($expression)) {
      return;
    }

    try {
      $this->search($expression, []);
    }
    catch (SyntaxErrorException $e) {
      // Remove newlines after nl2br() to make testing easier.
      return str_replace("\n", '', nl2br(SafeMarkup::checkPlain(trim($e->getMessage()))));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getErrors() {
    if (!function_exists('json_last_error')) {
      return [];
    }

    if (!$error = json_last_error()) {
      return [];
    }

    $message = [
      'message' => $this->utility->translateError($error),
      'variables' => [],
      'severity' => RfcLogLevel::ERROR,
    ];
    return [$message];
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!class_exists('JmesPath\AstRuntime')) {
      throw new RuntimeException($this->t('The JMESPath library is not installed.'));
    }
  }

}
