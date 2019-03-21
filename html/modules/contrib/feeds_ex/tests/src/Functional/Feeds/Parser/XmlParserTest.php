<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\XmlParser
 * @group feeds_ex
 */
class XmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'xml';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['/items/item'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderInvalidContext() {
    return [
      ['!! ', 'Invalid expression'],
    ];
  }

}
