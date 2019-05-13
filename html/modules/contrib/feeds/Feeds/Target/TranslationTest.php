<?php

namespace Drupal\Tests\feeds\Kernel\Feeds\Target;

use Drupal\language\ConfigurableLanguageInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Test for the entity field translation.
 *
 * @group feeds
 */
class TranslationTest extends FeedsKernelTestBase {

  use TaxonomyTestTrait;

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  protected $vocabulary;

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
    'taxonomy'
  ];

  /**
   * Feeds translation languages.
   *
   * @var array
   */
  protected $feedsTranslationLanguages = [
    'es',
    'nl',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    foreach ($this->feedsTranslationLanguages as $langcode) {
      $language = $this->container->get('entity.manager')->getStorage('configurable_language')->create([
        'id' => $langcode,
      ]);
      $language->save();
    }
    $this->createFieldWithStorage('field_alpha');

    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');

    $this->vocabulary = $this->createVocabulary();

    // Add a term reference field for the feed type.
    $this->createFieldWithStorage('field_tags', [
      'entity_type' => 'node',
      'bundle' => 'article',
      'type' => 'entity_reference',
      'storage' => [
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
      ],
      'field' => [
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [
            // Restrict selection of terms to a single vocabulary.
            'target_bundles' => [
              $this->vocabulary->id() => $this->vocabulary->id(),
            ],
          ],
        ],
      ],
    ]);

  }


  public function importContent($source) {
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $source,
    ]);
    $feed->import();
  }

  public function importSpanishContent() {
    $this->importContent($this->resourcesPath() . '/csv/translation/content_translation_es.csv');
  }

  public function importDutchContent() {
    $this->importContent($this->resourcesPath() . '/csv/translation/content_translation_nl.csv');
  }

  public function importMultipleLanguageContent() {
    $this->importContent($this->resourcesPath() . '/csv/translation/content_translation_multiple_languages.csv');
  }

  public function addMappings(array $mappings) {
    foreach ($mappings as $mapping_field) {
      $this->feedType->addMapping($mapping_field);
    }
  }

  /**
   * Tests importing a content with a translated field.
   */
  public function testTranslation() {
    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' =>'title',
    ]);
    $this->addMappings($this->getSpanishMapping());
    $this->feedType->save();
    $this->importSpanishContent();

    // Assert created node.
    $this->assertNodeCount(1);

    $nid = 1;
    $node = Node::load($nid);
    $this->assertEquals('HELLO WORLD', $node->title->value);

    $this->assertTrue($node->hasTranslation('es'));
    $this->assertEmpty($node->field_tags->referencedEntities());
    $translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $translation->title->value);
    $this->assertEquals($node->uid->value, $translation->uid->value);
    $this->assertNotEmpty($translation->field_tags->referencedEntities());
    $referenced_entities = $translation->field_tags->referencedEntities();
    $first_tag = reset($referenced_entities);
    $this->assertEquals("Termino de taxonomía", $first_tag->name->value);
  }

  /**
   * Test values from an other language are kept when not importing values for that language.
   */
  public function testMappingFieldsAnotherLanguageImport() {
    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' =>'title',
    ],
    [
      'processor_configuration' => [
        'update_existing' => 2,
        'skip_hash_heck' => TRUE,
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ]
    ]);

    $this->addMappings($this->getDefaultMappings());
    $this->addMappings($this->getSpanishMapping());

    $this->addMappings($this->getDutchMapping());

    $this->feedType->save();

    $this->importSpanishContent();

    $this->importDutchContent();

    $this->assertNodeCount(1);

    $nid = 1;
    $node = Node::load($nid);

    $this->assertEquals('HELLO WORLD', $node->title->value);
    $this->assertTrue($node->hasTranslation('es'));

    $spanish_translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $spanish_translation->title->value);
    $this->assertTrue($node->hasTranslation('nl'));

    $dutch_translation = $node->getTranslation('nl');
    $this->assertEquals('HALLO WERELD', $dutch_translation->title->value);

  }

  public function testValuesForMultipleLanguagesAreImported() {

    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' =>'title',
    ],
    [
      'processor_configuration' => [
        'update_existing' => 2,
        'skip_hash_heck' => TRUE,
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ]
    ]);

    $this->addMappings($this->getSpanishMapping());

    $this->addMappings($this->getDutchMapping());

    $this->feedType->save();

    $this->importMultipleLanguageContent();

    $this->assertNodeCount(1);

    $nid = 1;
    $node = Node::load($nid);

    $this->assertEquals('HELLO WORLD', $node->title->value);
    $this->assertTrue($node->hasTranslation('es'));

    $spanish_translation = $node->getTranslation('es');
    $this->assertEquals('HOLA MUNDO', $spanish_translation->title->value);
    $this->assertEquals($node->uid->value, $spanish_translation->uid->value);
    $this->assertNotEmpty($spanish_translation->field_tags->referencedEntities());
    $spanish_referenced_entities = $spanish_translation->field_tags->referencedEntities();
    $spanish_translation_first_tag = reset($spanish_referenced_entities);
    $this->assertEquals("Termino de taxonomía", $spanish_translation_first_tag->name->value);

    $this->assertTrue($node->hasTranslation('nl'));

    $dutch_translation = $node->getTranslation('nl');
    $this->assertEquals('HALLO WERELD', $dutch_translation->title->value);
    $this->assertNotEmpty($dutch_translation->field_tags->referencedEntities());
    $dutch_referenced_entities = $dutch_translation->field_tags->referencedEntities();
    $dutch_translation_first_tag = reset($dutch_referenced_entities);
    $this->assertEquals("Taxonomie termijn", $dutch_translation_first_tag->name->value);

  }

  public function testValuesAreImportedAfterALanguageIsRemoved() {
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' =>'title',
    ],
    [
      'processor_configuration' => [
        'update_existing' => 2,
        'skip_hash_heck' => TRUE,
        'authorize' => FALSE,
        'values' => [
          'type' => 'article',
        ],
      ],
    ]);

    $this->addMappings($this->getSpanishMapping());

    $this->feedType->save();
//    $this->importSpanishContent();

    $spanish_language = $this->container->get('entity.manager')->getStorage('configurable_language')->loadByProperties(['id' => 'es']);
    if (!empty($spanish_language['es']) && $spanish_language['es'] instanceof ConfigurableLanguageInterface) {
      $spanish_language['es']->delete();
    }

    $this->importSpanishContent();

    $this->assertNodeCount(1);

    $nid = 1;
    $node = Node::load($nid);

    $this->assertEquals('HELLO WORLD', $node->title->value);
    $this->assertFalse($node->hasTranslation('es'));

  }

  public function getDutchMapping() {
    return [
      [
        'target' => 'title',
        'map' => ['value' => 'title_nl'],
        'settings' => [
          'language' => 'nl',
        ],
      ],
      [
        'target' => 'field_tags',
        'map' => ['target_id' => 'terms_nl'],
        'settings' => [
          'reference_by' => 'name',
          'language' => 'nl',
          'autocreate' => 1,
        ],
      ]
    ];
  }

  public function getSpanishMapping() {
    return [
      [
        'target' => 'title',
        'map' => ['value' => 'title_es'],
        'settings' => [
          'language' => 'es',
        ],
      ],
      [
        'target' => 'field_tags',
        'map' => ['target_id' => 'terms_es'],
        'settings' => [
          'reference_by' => 'name',
          'language' => 'es',
          'autocreate' => 1,
        ],
      ]
    ];
  }

  /**
   * Returns default mappings for tests.
   *
   * Can be overridden by specific tests.
   *
   * @return array
   *   A list of default mappings.
   */
  public function getDefaultMappings() {
    return [
      [
        'target' => 'feeds_item',
        'map' => ['guid' => 'guid'],
        'unique' => ['guid' => TRUE],
        'settings' => [],
      ],
      [
        'target' => 'title',
        'map' => ['value' => 'title'],
        'settings' => [
          'language' => NULL,
        ],
        'unique' => [
          'value' => 1,
        ],
      ],
    ];
  }

}
