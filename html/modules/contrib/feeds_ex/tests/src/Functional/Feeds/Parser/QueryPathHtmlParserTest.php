<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathHtmlParser
 * @group feeds_ex
 */
class QueryPathHtmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'querypathhtml';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['.post'],
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
