<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use Drupal\feeds_ex\Utility\JsonUtility;

/**
 * Base class for JSON based parsers.
 */
abstract class JsonParserBase extends ParserBase {

  /**
   * The JSON helper class.
   *
   * @var \Drupal\feeds_ex\Utility\JsonUtility
   */
  protected $utility;

  /**
   * Constructs a JsonParserBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\feeds_ex\Utility\JsonUtility $utility
   *   The JSON helper class.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, JsonUtility $utility) {
    $this->utility = $utility;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function startErrorHandling() {
    // Clear the json errors from previous parsing.
    json_decode('{}');
  }

}
