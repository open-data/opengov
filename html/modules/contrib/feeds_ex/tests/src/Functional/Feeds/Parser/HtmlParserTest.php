<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\HtmlParser
 * @group feeds_ex
 */
class HtmlParserTest extends ParserTestBase {

  use ContextTestTrait;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'html';

  /**
   * {@inheritdoc}
   */
  public function dataProviderValidContext() {
    return [
      ['//div[@class="post"]'],
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
