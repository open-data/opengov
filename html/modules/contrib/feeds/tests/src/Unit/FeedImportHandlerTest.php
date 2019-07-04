<?php

namespace Drupal\Tests\feeds\Unit;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedImportHandler;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResultInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\feeds\FeedImportHandler
 * @group feeds
 */
class FeedImportHandlerTest extends FeedsUnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * The feed entity.
   *
   * @var \Drupal\feeds\FeedInterface
   */
  protected $feed;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->dispatcher = new EventDispatcher();
    $this->handler = new FeedImportHandler($this->dispatcher);
    $this->handler->setStringTranslation($this->getStringTranslationStub());

    $this->feed = $this->getMock(FeedInterface::class);
    $this->feed->expects($this->any())
      ->method('id')
      ->will($this->returnValue(10));
    $this->feed->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_feed'));
  }

  /**
   * @covers ::startBatchImport
   */
  public function testStartBatchImport() {
    $this->feed->expects($this->once())
      ->method('lock')
      ->will($this->returnValue($this->feed));

    $this->handler->startBatchImport($this->feed);
  }

  /**
   * @covers ::batchFetch
   * @covers ::batchParse
   * @covers ::batchProcess
   * @covers ::doFetch
   * @covers ::doParse
   * @covers ::doProcess
   */
  public function testBatch() {
    $this->addDefaultEventListeners();

    $this->feed->expects($this->exactly(3))
      ->method('saveStates');

    $this->handler->batchFetch($this->feed);
    $this->handler->batchParse($this->feed);
    $this->handler->batchProcess($this->feed, $this->getMock(ItemInterface::class));
  }

  /**
   * Adds default listeners to event dispatcher.
   */
  protected function addDefaultEventListeners() {
    $fetcher_result = $this->getMock(FetcherResultInterface::class);
    $parser_result = $this->getMock(ParserResultInterface::class);

    $this->dispatcher->addListener(FeedsEvents::FETCH, function (FetchEvent $event) use ($fetcher_result) {
      $event->setFetcherResult($fetcher_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PARSE, function (ParseEvent $event) use ($fetcher_result, $parser_result) {
      $this->assertSame($event->getFetcherResult(), $fetcher_result);
      $event->setParserResult($parser_result);
    });

    $this->dispatcher->addListener(FeedsEvents::PROCESS, function (ProcessEvent $event) use ($parser_result) {
      $this->assertInstanceOf(ItemInterface::class, $event->getItem());
    });
  }

}
