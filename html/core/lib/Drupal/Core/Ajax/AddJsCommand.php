<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command for adding JS to the page via ajax.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.add_js()
 * defined in misc/ajax.js.
 *
 * @see misc/ajax.js
 *
 * @ingroup ajax
 */
class AddJsCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string|null
   */
  protected $selector;

  /**
   * An array containing the attributes of the scripts to be added to the page.
   *
   * @var string[]
   */
  protected $scripts;

  /**
   * The DOM manipulation method to be used.
   *
   * @var string[]
   */
  protected $method;

  /**
   * Constructs an AddJsCommand.
   *
   * @param string|null $selector
   *   A CSS selector.
   * @param array $scripts
   *   An array containing the attributes of the scripts to be added to the page.
   * @param string $method
   *   The DOM manipulation method to be used.
   */
  public function __construct($selector, array $scripts, $method = 'appendChild') {
    $this->selector = $selector;
    $this->scripts = $scripts;
    $this->method = $method;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'add_js',
      'selector' => $this->selector,
      'data' => $this->scripts,
      'method' => $this->method,
    ];
  }

}
