<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JsonPathLinesParser
 * @group feeds_ex
 */
class JsonPathLinesParserTest extends ParserTestBase {

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'jsonpathlines';

  /**
   * Placeholder test.
   *
   * @todo remove when tests are implemented for this parser.
   */
  public function test() {
    $this->drupalGet('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping');
  }

}
