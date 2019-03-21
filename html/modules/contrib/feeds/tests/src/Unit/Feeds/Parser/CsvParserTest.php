<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Parser;

use Drupal\feeds\Feeds\Parser\CsvParser;
use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\State;
use Drupal\feeds\StateInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Parser\CsvParser
 * @group feeds
 */
class CsvParserTest extends FeedsUnitTestCase {

  /**
   * The Feeds parser plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Parser\CsvParser
   */
  protected $parser;

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * The state object.
   *
   * @var \Drupal\feeds\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->feedType = $this->getMock('Drupal\feeds\FeedTypeInterface');
    $this->feedType->method('getMappingSources')
      ->will($this->returnValue([]));
    $configuration = ['feed_type' => $this->feedType, 'line_limit' => 3];
    $this->parser = new CsvParser($configuration, 'csv', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->state = new State();

    $this->feed = $this->getMock('Drupal\feeds\FeedInterface');
    $this->feed->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($this->feedType));
  }

  /**
   * Tests parsing a CSV file that succeeds.
   *
   * @covers ::parse
   */
  public function testParse() {
    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->will($this->returnValue($this->parser->defaultFeedConfiguration()));

    $file = $this->resourcesPath() . '/csv/example.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('Header A'), '"1"');

    // Parse again. Tests batching.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);

    $this->assertSame(count($result), 3);
    $this->assertSame($result[0]->get('Header B'), "new\r\nline 2");
  }

  /**
   * Tests parsing an empty CSV file.
   *
   * @covers ::parse
   * @expectedException \Drupal\feeds\Exception\EmptyFeedException
   */
  public function testEmptyFeed() {
    touch('vfs://feeds/empty_file');
    $result = new FetcherResult('vfs://feeds/empty_file');
    $this->parser->parse($this->feed, $result, $this->state);
  }

  /**
   * Tests parsing a file with a few extra blank lines.
   */
  public function testFeedWithExtraBlankLines() {
    // Set an high line limit.
    $configuration = ['feed_type' => $this->feedType, 'line_limit' => 100];
    $this->parser = new CsvParser($configuration, 'csv', []);
    $this->parser->setStringTranslation($this->getStringTranslationStub());

    $this->feed->expects($this->any())
      ->method('getConfigurationFor')
      ->with($this->parser)
      ->will($this->returnValue($this->parser->defaultFeedConfiguration()));

    $file = $this->resourcesPath() . '/csv/with-empty-lines.csv';
    $fetcher_result = new FetcherResult($file);

    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(9, $result);

    // Parse again.
    $result = $this->parser->parse($this->feed, $fetcher_result, $this->state);
    $this->assertCount(0, $result);

    // Assert that parsing has finished.
    $this->assertEquals(StateInterface::BATCH_COMPLETE, $this->state->progress);
  }

  /**
   * @covers ::getMappingSources
   */
  public function testGetMappingSources() {
    // Not really much to test here.
    $this->assertSame([], $this->parser->getMappingSources());
  }

}
