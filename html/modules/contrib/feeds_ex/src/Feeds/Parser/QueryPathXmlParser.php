<?php

namespace Drupal\feeds_ex\Feeds\Parser;

use RuntimeException;
use QueryPath;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use QueryPath\DOMQuery;
use QueryPath\CSS\ParseException;

/**
 * Defines a XML parser using QueryPath.
 *
 * @FeedsParser(
 *   id = "querypathxml",
 *   title = @Translation("QueryPath XML"),
 *   description = @Translation("Parse XML with QueryPath."),
 *   arguments = {"@feeds_ex.xml_utility"}
 * )
 */
class QueryPathXmlParser extends XmlParser {

  /**
   * Options passed to QueryPath.
   *
   * @var array
   */
  protected $queryPathOptions = [
    'ignore_parser_warnings' => TRUE,
    'use_parser' => 'xml',
    'strip_low_ascii' => FALSE,
    'replace_entities' => FALSE,
    'omit_xml_declaration' => TRUE,
    'encoding' => 'UTF-8',
  ];

  /**
   * {@inheritdoc}
   */
  protected function executeContext(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $document = $this->prepareDocument($feed, $fetcher_result);
    $query_path = QueryPath::with($document, $this->configuration['context']['value'], $this->queryPathOptions);

    if (!$state->total) {
      $state->total = $query_path->size();
    }

    $start = (int) $state->pointer;
    $state->pointer = $start + $this->configuration['line_limit'];
    $state->progress($state->total, $state->pointer);

    return $query_path->slice($start, $this->configuration['line_limit']);
  }

  /**
   * {@inheritdoc}
   */
  protected function executeSourceExpression($machine_name, $expression, $row) {
    $result = QueryPath::with($row, $expression, $this->queryPathOptions);

    if ($result->size() == 0) {
      return;
    }

    $config = $this->configuration['sources'][$machine_name];

    $return = [];

    if (strlen($config['attribute'])) {
      foreach ($result as $node) {
        $return[] = $node->attr($config['attribute']);
      }
    }
    elseif (!empty($config['inner'])) {
      foreach ($result as $node) {
        $return[] = $node->innerXML();
      }
    }
    elseif (!empty($config['raw'])) {
      foreach ($result as $node) {
        $return[] = $this->getRawValue($node);
      }
    }
    else {
      foreach ($result as $node) {
        $return[] = $node->text();
      }
    }

    // Return a single value if there's only one value.
    return count($return) === 1 ? reset($return) : $return;
  }

  /**
   * Returns the raw value.
   *
   * @param \QueryPath\DOMQuery $node
   *   The DOMQuery object to return a raw value for.
   *
   * @return string
   *   A raw string value.
   */
  protected function getRawValue(DOMQuery $node) {
    return $node->xml();
  }

  /**
   * {@inheritdoc}
   */
  protected function validateExpression(&$expression) {
    $expression = trim($expression);
    if (!$expression) {
      return;
    }
    try {
      $parser = QueryPath::with(NULL, $expression);
    }
    catch (ParseException $e) {
      return SafeMarkup::checkPlain($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function configFormTableHeader() {
    return [
      'attribute' => $this->t('Attribute'),
    ] + parent::configFormTableHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function configFormTableColumn(FormStateInterface $form_state, array $values, $column, $machine_name) {
    switch ($column) {
      case 'attribute':
        return [
          '#type' => 'textfield',
          '#title' => $this->t('Attribute name'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($values['attribute']) ? $values['attribute'] : '',
          '#size' => 10,
          '#maxlength' => 1024,
        ];

      default:
        return parent::configFormTableColumn($form_state, $values, $column, $machine_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLibrary() {
    if (!class_exists('QueryPath')) {
      throw new RuntimeException($this->t('The QueryPath library is not installed.'));
    }
  }

}
