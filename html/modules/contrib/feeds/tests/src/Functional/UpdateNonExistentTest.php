<?php

namespace Drupal\Tests\feeds\Functional;

use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\FeedTypeInterface;

/**
 * Tests the feature of updating items that are no longer available in the feed.
 *
 * @group feeds
 */
class UpdateNonExistentTest extends FeedsBrowserTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'fetcher' => 'directory',
      'fetcher_configuration' => [
        'allowed_extensions' => 'atom rss rss1 rss2 opml xml',
      ],
      'processor_configuration' => [
        'authorize' => FALSE,
        'update_existing' => ProcessorInterface::UPDATE_EXISTING,
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);
  }

  /**
   * Tests 'Unpublish non-existent' option using a Batch.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get unpublished when the 'update_non_existent' setting is set to
   * 'node_unpublish_action' and when performing an import using the UI.
   */
  public function testUnpublishNonExistentItemsWithBatch() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'node_unpublish_action';
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Reload feed and assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    $this->assertText('Created 6 Articles.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that one node was unpublished.
    $node = $this->getNodeByTitle('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters');
    $this->assertFalse($node->isPublished());

    // Manually publish the node.
    $node->status = 1;
    $node->setTitle('Lorem');
    $node->save();
    $this->assertTrue($node->isPublished(), 'Node is published');

    // Import the same file again to ensure that the node does not get
    // unpublished again (since the node was already unpublished during the
    // previous import).
    $this->batchImport($feed);
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->isPublished(), 'Node is not updated');

    // Re-import the original feed to ensure the unpublished node is updated,
    // even though the item is the same since the last time it was available in
    // the feed. Fact is that the node was not available in the previous import
    // and that should be seen as a change.
    $feed = $this->reloadFeed($feed);
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->batchImport($feed);
    $node = $this->reloadEntity($node);
    $this->assertText('Updated 1 Article.');
    static::assertEquals('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters', $node->getTitle());
  }

  /**
   * Tests 'Delete non-existent' option using a Batch.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get deleted when the 'update_non_existent' setting is set to
   * '_delete' and when performing an import using the UI.
   */
  public function testDeleteNonExistentItemsWithBatch() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);
    $this->batchImport($feed);

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    $this->assertText('Created 6 Articles.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->batchImport($feed);

    // Assert that one node is removed.
    $feed = $this->reloadFeed($feed);
    $this->assertText('Cleaned 1 Article.');
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // Re-import the original feed to import the removed node again.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->batchImport($feed);
    $feed = $this->reloadFeed($feed);
    $this->assertText('Created 1 Article.');
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

  /**
   * Tests 'Unpublish non-existent' option using cron.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get unpublished when the 'update_non_existent' setting is set to
   * 'node_unpublish_action' and when performing an import using cron.
   */
  public function testUnpublishNonExistentItemsWithCron() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = 'node_unpublish_action';
    $this->feedType->getProcessor()->setConfiguration($config);
    // Set the import period to run as often as possible.
    $this->feedType->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Run cron to import.
    $this->cronRun();

    // Reload feed and assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->cronRun();

    // Assert that one node was unpublished.
    $node = $this->getNodeByTitle('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters');
    $this->assertFalse($node->isPublished());

    // Manually publish the node.
    $node->status = 1;
    $node->setTitle('Lorem');
    $node->save();
    $this->assertTrue($node->isPublished(), 'Node is published');

    // Import the same file again to ensure that the node does not get
    // unpublished again (since the node was already unpublished during the
    // previous import).
    $this->cronRun();
    $node = $this->reloadEntity($node);
    $this->assertTrue($node->isPublished(), 'Node is not updated');

    // Re-import the original feed to ensure the unpublished node is updated,
    // even though the item is the same since the last time it was available in
    // the feed. Fact is that the node was not available in the previous import
    // and that should be seen as a change.
    $feed = $this->reloadFeed($feed);
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->cronRun();
    $node = $this->reloadEntity($node);
    static::assertEquals('Egypt, Hamas exchange fire on Gaza frontier, 1 dead - Reuters', $node->getTitle());
  }

  /**
   * Tests 'Delete non-existent' option using cron.
   *
   * Tests that previously imported items that are no longer available in the
   * feed get deleted when the 'update_non_existent' setting is set to
   * '_delete' and when performing an import using cron.
   */
  public function testDeleteNonExistentItemsWithCron() {
    // Set 'update_non_existent' setting to 'unpublish'.
    $config = $this->feedType->getProcessor()->getConfiguration();
    $config['update_non_existent'] = ProcessorInterface::DELETE_NON_EXISTENT;
    $this->feedType->getProcessor()->setConfiguration($config);
    // Set the import period to run as often as possible.
    $this->feedType->setImportPeriod(FeedTypeInterface::SCHEDULE_CONTINUOUSLY);
    $this->feedType->save();

    // Create a feed and import first file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/rss/googlenewstz.rss2',
    ]);

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);

    // Import an "updated" version of the file from which one item is removed.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz_missing.rss2');
    $feed->save();
    $this->cronRun();

    // Assert that one node is removed.
    $feed = $this->reloadFeed($feed);
    static::assertEquals(5, $feed->getItemCount());
    $this->assertNodeCount(5);

    // Re-import the original feed to import the removed node again.
    $feed->setSource($this->resourcesPath() . '/rss/googlenewstz.rss2');
    $feed->save();
    $this->cronRun();
    $feed = $this->reloadFeed($feed);
    static::assertEquals(6, $feed->getItemCount());
    $this->assertNodeCount(6);
  }

}
