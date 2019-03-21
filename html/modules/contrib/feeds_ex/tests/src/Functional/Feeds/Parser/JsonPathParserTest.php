<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JsonPathParser
 * @group feeds_ex
 */
class JsonPathParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'jsonpath';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['$.items.*'],
      ['!! ', '!!'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [];
  }

}
