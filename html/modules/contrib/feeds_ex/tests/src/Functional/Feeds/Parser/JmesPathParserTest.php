<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JmesPathParser
 * @group feeds_ex
 */
class JmesPathParserTest extends ParserTestBase {

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'jmespath';

  /**
   * Placeholder test.
   *
   * @todo remove when tests are implemented for this parser.
   */
  public function test() {
    $this->drupalGet('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping');
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['items'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['!! ', 'Syntax error at character'],
    ];
  }

}
