<?php

namespace Drupal\Tests\feeds\Functional\Feeds\Fetcher;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use SimpleXMLElement;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Fetcher\HttpFetcher
 * @group feeds
 */
class HttpFetcherTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'feeds',
    'node',
    'user',
    'file',
    'block',
    'taxonomy',
  ];

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

    // Add body field.
    node_add_body_field($this->nodeType);

    // Add taxonomy reference field.
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    $this->createFieldWithStorage('field_tags', [
      'type' => 'entity_reference',
      'label' => 'Tags',
      'storage' => [
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ],
      'field' => [
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [
              'tags' => 'tags',
            ],
          ],
        ],
      ],
    ]);

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'fetcher' => 'http',
      'mappings' => [
        [
          'target' => 'title',
          'map' => ['value' => 'title'],
        ],
        [
          'target' => 'body',
          'map' => ['value' => 'description'],
        ],
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid', 'url' => 'url'],
          'unique' => ['guid' => TRUE],
        ],
        [
          'target' => 'created',
          'map' => ['value' => 'timestamp'],
        ],
        [
          'target' => 'field_tags',
          'map' => ['target_id' => 'tags'],
          'settings' => ['autocreate' => TRUE],
        ],
      ],
      'processor_configuration' => ['values' => ['type' => 'article']],
      'import_period' => FeedTypeInterface::SCHEDULE_NEVER,
    ]);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests importing a RSS feed using the HTTP fetcher.
   */
  public function testHttpImport() {
    $filepath = drupal_get_path('module', 'feeds') . '/tests/resources/rss/googlenewstz.rss2';

    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $this->drupalGet('feed/' . $feed->id());
    $this->clickLink(t('Import'));
    $this->drupalPostForm(NULL, [], t('Import'));
    $this->assertText('Created 6');
    $this->assertNodeCount(6);

    $xml = new SimpleXMLElement($filepath, 0, TRUE);

    $expected_terms = [
      1 => [],
      2 => ['Top Stories'],
      3 => ['Top Stories'],
      4 => ['Top Stories 2'],
      5 => ['Top Stories 2'],
      6 => ['Top Stories 3'],
    ];

    foreach (range(1, 6) as $nid) {
      $item = $xml->channel->item[$nid - 1];
      $node = Node::load($nid);
      $this->assertEquals($node->title->value, (string) $item->title);
      $this->assertEquals($node->body->value, (string) $item->description);
      $this->assertEquals($node->feeds_item->guid, (string) $item->guid);
      $this->assertEquals($node->feeds_item->url, (string) $item->link);
      $this->assertEquals($node->created->value, strtotime((string) $item->pubDate));

      $terms = [];
      foreach ($node->field_tags->referencedEntities() as $term) {
        $terms[] = $term->label();
      }
      $this->assertEquals($expected_terms[$nid], $terms);
    }

    // Test cache.
    $this->drupalPostForm('feed/' . $feed->id() . '/import', [], t('Import'));
    $this->assertText('The feed has not been updated.');

    // Import again.
    \Drupal::cache('feeds_download')->deleteAll();
    $this->drupalPostForm('feed/' . $feed->id() . '/import', [], t('Import'));
    $this->assertText('There are no new');

    // Test force-import.
    \Drupal::cache('feeds_download')->deleteAll();
    $configuration = $this->feedType->getProcessor()->getConfiguration();
    $configuration['skip_hash_check'] = TRUE;
    $configuration['update_existing'] = ProcessorInterface::UPDATE_EXISTING;
    $this->feedType->getProcessor()->setConfiguration($configuration);
    $this->feedType->save();
    $this->drupalPostForm('feed/' . $feed->id() . '/import', [], t('Import'));
    $this->assertNodeCount(6);
    $this->assertText('Updated 6');

    // Delete items.
    $this->clickLink(t('Delete items'));
    $this->drupalPostForm(NULL, [], t('Delete items'));
    $this->assertNodeCount(0);
    $this->assertText('Deleted 6');
  }

}
