<?php

namespace Drupal\Tests\feeds_ex\Unit\Feeds\Parser;

use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds_ex\Feeds\Parser\JmesPathParser;
use Drupal\feeds_ex\Messenger\TestMessenger;
use Drupal\feeds_ex\Utility\JsonUtility;
use JmesPath\AstRuntime;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\JmesPathParser
 * @group feeds_ex
 */
class JmesPathParserTest extends ParserTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $configuration = ['feed_type' => $this->feedType];
    $utility = new JsonUtility();
    $utility->setStringTranslation($this->getStringTranslationStub());
    $this->parser = new JmesPathParser($configuration, 'jmespath', [], $utility);
    $this->parser->setStringTranslation($this->getStringTranslationStub());
    $this->parser->setFeedsExMessenger(new TestMessenger());

    // Set JMESPath runtime factory.
    $factoryMock = $this->getMock('Drupal\feeds_ex\JmesRuntimeFactoryInterface');
    $factoryMock->expects($this->any())
      ->method('createRuntime')
      ->will($this->returnCallback(
        function () {
          return new AstRuntime();
        }
      ));
    $this->parser->setRuntimeFactory($factoryMock);
  }

  /**
   * Tests simple parsing.
   */
  public function testSimpleParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'));

    $config = [
      'context' => [
        'value' => 'items',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
        ],
      ],
    ];
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);
    foreach ($result as $delta => $item) {
      $this->assertSame('I am a title' . $delta, $item->get('title'));
      $this->assertSame('I am a description' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests a EUC-JP (Japanese) encoded file.
   *
   * This implicitly tests Base's encoding conversion.
   */
  public function testEucJpEncoded() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test_jp.json'));

    $config = [
      'context' => [
        'value' => 'items',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
        ],
      ],
      'source_encoding' => ['EUC-JP'],
    ];
    $this->parser->setConfiguration($config);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 3);

    foreach ($result as $delta => $item) {
      $this->assertSame('私はタイトルです' . $delta, $item->get('title'));
      $this->assertSame('私が説明してい' . $delta, $item->get('description'));
    }
  }

  /**
   * Tests batch parsing.
   */
  public function testBatchParsing() {
    $fetcher_result = new RawFetcherResult(file_get_contents($this->moduleDir . '/tests/resources/test.json'));

    $config = [
      'context' => [
        'value' => 'items',
      ],
      'sources' => [
        'title' => [
          'name' => 'Title',
          'value' => 'title',
        ],
        'description' => [
          'name' => 'Title',
          'value' => 'description',
        ],
      ],
      'line_limit' => 1,
    ];
    $this->parser->setConfiguration($config);

    foreach (range(0, 2) as $delta) {
      $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
      $this->assertSame(count($result), 1);
      $this->assertSame('I am a title' . $delta, $result[0]->get('title'));
      $this->assertSame('I am a description' . $delta, $result[0]->get('description'));
    }

    // We should be out of items.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertSame(count($result), 0);
  }

  /**
   * Tests JMESPath validation.
   */
  public function testValidateExpression() {
    // Invalid expression.
    $expression = '!! ';
    $this->assertStringStartsWith('Syntax error at character', $this->invokeMethod($this->parser, 'validateExpression', [&$expression]));

    // Test that value was trimmed.
    $this->assertSame($expression, '!!', 'Value was trimmed.');

    // Empty string.
    $empty = '';
    $this->assertSame(NULL, $this->invokeMethod($this->parser, 'validateExpression', [&$empty]));
  }

  /**
   * Tests parsing invalid context expression.
   *
   * @expectedException RuntimeException
   * @expectedExceptionMessage The context expression must return an object or array.
   */
  public function testInvalidContextExpression() {
    $config = [
      'context' => [
        'value' => 'items',
      ],
      'sources' => [],
    ];
    $this->parser->setConfiguration($config);

    $this->parser->parse($this->feed, new RawFetcherResult('{"items": "not an array"}'), $this->state);
  }

  /**
   * Tests parsing invalid JSON.
   *
   * @expectedException RuntimeException
   * @expectedExceptionMessage The JSON is invalid.
   */
  public function testInvalidJson() {
    $config = [
      'context' => [
        'value' => 'items',
      ],
      'sources' => [],
    ];
    $this->parser->setConfiguration($config);

    $this->parser->parse($this->feed, new RawFetcherResult('invalid json'), $this->state);
  }

  /**
   * Tests empty feed handling.
   */
  public function testEmptyFeed() {
    $this->parser->parse($this->feed, new RawFetcherResult(' '), $this->state);
    $this->assertEmptyFeedMessage($this->parser->getMessenger()->getMessages());
  }

}
