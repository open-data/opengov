<?php

namespace Drupal\Tests\feeds\Functional\Controller;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * Lists the feed items belonging to a feed.
 *
 * @group feeds
 */
class ItemListControllerTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'feeds',
    'feeds_test_entity',
  ];

  /**
   * Tests listing items for an entity type without a link template.
   */
  public function testListItemsForAnEntityTypeWithoutLinkTemplate() {
    $feed_type = $this->createFeedType([
      'parser' => 'csv',
      'processor' => 'entity:feeds_test_entity_test_no_links',
      'processor_configuration' => [
        'authorize' => FALSE,
      ],
      'custom_sources' => [
        'title' => [
          'label' => 'title',
          'value' => 'title',
          'machine_name' => 'title',
        ],
      ],
      'mappings' => [
        [
          'target' => 'name',
          'map' => ['value' => 'title'],
        ],
      ],
    ]);

    // Import CSV file.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/csv/content.csv',
    ]);
    $feed->import();

    // Go to the items page and assert that two items are shown there.
    $this->drupalGet('/feed/1/list');
    $this->assertNoText('The website encountered an unexpected error.');
    $this->assertText('Lorem ipsum');
    $this->assertText('Ut wisi enim ad minim veniam');
  }

}
