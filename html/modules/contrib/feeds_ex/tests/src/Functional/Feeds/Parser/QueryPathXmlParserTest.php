<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser
 * @group feeds_ex
 */
class QueryPathXmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'querypathxml';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['items item'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['!! ', 'CSS selector is not well formed.'],
    ];
  }

}
