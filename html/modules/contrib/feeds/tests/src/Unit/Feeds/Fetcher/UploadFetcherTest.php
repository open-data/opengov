<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Fetcher;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Fetcher\UploadFetcher;
use Drupal\feeds\StateInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\UploadFetcher
 * @group feeds
 */
class UploadFetcherTest extends FeedsUnitTestCase {

  /**
   * The file entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The Feeds fetcher plugin under test.
   *
   * @var \Drupal\feeds\Feeds\Fetcher\UploadFetcher
   */
  protected $fetcher;

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

    $this->fileStorage = $this->getMock(EntityStorageInterface::class);
    $entity_manager = $this->getMock(EntityTypeManagerInterface::class);
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('file')
      ->will($this->returnValue($this->fileStorage));

    $this->fetcher = new UploadFetcher(
      ['feed_type' => $this->getMock(FeedTypeInterface::class)],
      'test_plugin',
      ['plugin_type' => 'fetcher'],
      $this->getMock(FileUsageInterface::class),
      $entity_manager,
      $this->getMockStreamWrapperManager()
    );

    $this->fetcher->setStringTranslation($this->getStringTranslationStub());

    $this->state = $this->getMock(StateInterface::class);
  }

  /**
   * Tests a fetch that succeeds.
   *
   * @covers ::fetch
   */
  public function testFetch() {
    touch('vfs://feeds/test_file');

    $feed = $this->getMock(FeedInterface::class);
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file'));
    $this->fetcher->fetch($feed, $this->state);
  }

  /**
   * Tests a fetch that fails.
   *
   * @covers ::fetch
   * @expectedException \RuntimeException
   */
  public function testFetchException() {
    $feed = $this->getMock(FeedInterface::class);
    $feed->expects($this->any())
      ->method('getSource')
      ->will($this->returnValue('vfs://feeds/test_file'));
    $this->fetcher->fetch($feed, $this->state);
  }

  /**
   * @covers ::onFeedDeleteMultiple
   */
  public function testOnFeedDeleteMultiple() {
    $feed = $this->getMock(FeedInterface::class);
    $feed->expects($this->exactly(2))
      ->method('getConfigurationFor')
      ->with($this->fetcher)
      ->will($this->returnValue(['fid' => 10] + $this->fetcher->defaultFeedConfiguration()));

    $feeds = [$feed, $feed];
    $this->fetcher->onFeedDeleteMultiple($feeds);
  }

}
