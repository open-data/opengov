<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Language
 * @group feeds
 */
class LanguageTest extends FeedsKernelTestBase {

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $language = $this->container->get('entity.manager')->getStorage('configurable_language')->create([
      'id' => 'es',
    ]);
    $language->save();

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid',
      'title',
      'langcode',
    ]);
  }

  /**
   * Tests importing a content with a specific language.
   */
  public function testImportLanguage() {
    $this->feedType->addMapping([
      'target' => 'langcode',
      'map' => ['value' => 'langcode'],
    ]);
    $this->feedType->save();

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_language.csv',
    ]);
    $feed->import();

    // Assert three created nodes.
    $this->assertNodeCount(2);

    $expected = [
      1 => 'und',
      2 => 'es',
    ];
    foreach ($expected as $nid => $value) {
      $node = Node::load($nid);
      $this->assertEquals($value, $node->langcode->value);
    }
  }

}
