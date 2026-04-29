<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\KernelTests\KernelTestBase;

// Workaround to support tests against both Drupal 10.1 and Drupal 11.0.
// @todo Remove once we depend on Drupal 10.2.
if (!trait_exists(EntityReferenceFieldCreationTrait::class)) {
  class_alias('\Drupal\Tests\field\Traits\EntityReferenceTestTrait', EntityReferenceFieldCreationTrait::class);
}

/**
 * Tests export functionality.
 *
 * @coversDefaultClass \Drupal\default_content\Normalizer\ContentEntityNormalizer
 * @group default_content
 */
class TranslationNormalizerTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'serialization',
    'default_content',
    'content_translation',
    'language',
    'node',
  ];

  /**
   * The tested default content exporter.
   *
   * @var \Drupal\default_content\Exporter
   */
  protected $exporter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create a node type with a paragraphs field.
    NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ])->save();

    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests menu_link_content entities.
   */
  public function testTranslationNormalization() {

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'page',
      'title' => 'English Title',
      'langcode' => 'en',
    ]);
    $node->addTranslation('de', ['title' => 'German Title']);
    $node->addTranslation('fr', ['title' => 'French Title']);
    $node->save();

    /** @var \Drupal\default_content\Normalizer\ContentEntityNormalizerInterface $normalizer */
    $normalizer = \Drupal::service('default_content.content_entity_normalizer');

    $normalized = $normalizer->normalize($node);

    $expected = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => 'node',
        'uuid' => $node->uuid(),
        'bundle' => 'page',
        'default_langcode' => 'en',
      ],
      'default' => [
        'revision_uid' => [
          0 => [
            'target_id' => 0,
          ],
        ],
        'status' => [
          0 => [
            'value' => TRUE,
          ],
        ],
        'uid' => [
          0 => [
            'target_id' => 0,
          ],
        ],
        'title' => [
          0 => [
            'value' => 'English Title',
          ],
        ],
        'created' => [
          0 => [
            'value' => $node->getCreatedTime(),
          ],
        ],
        'promote' => [
          0 => [
            'value' => $node->isPromoted(),
          ],
        ],
        'sticky' => [
          0 => [
            'value' => FALSE,
          ],
        ],
      ],
      'translations' => [
        'fr' => [
          'status' => [
            0 => [
              'value' => TRUE,
            ],
          ],
          'uid' => [
            0 => [
              'target_id' => 0,
            ],
          ],
          'title' => [
            0 => [
              'value' => 'French Title',
            ],
          ],
          'created' => [
            0 => [
              'value' => $node->getCreatedTime(),
            ],
          ],
          'promote' => [
            0 => [
              'value' => $node->isPromoted(),
            ],
          ],
          'sticky' => [
            0 => [
              'value' => FALSE,
            ],
          ],
        ],
        'de' => [
          'status' => [
            0 => [
              'value' => TRUE,
            ],
          ],
          'uid' => [
            0 => [
              'target_id' => 0,
            ],
          ],
          'title' => [
            0 => [
              'value' => 'German Title',
            ],
          ],
          'created' => [
            0 => [
              'value' => $node->getCreatedTime(),
            ],
          ],
          'promote' => [
            0 => [
              'value' => $node->isPromoted(),
            ],
          ],
          'sticky' => [
            0 => [
              'value' => FALSE,
            ],
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $normalized);

    $node->delete();

    // Denormalize it back, add an extra translation that doesn't exist, that
    // should be ignored.
    $normalized['translations']['es'] = $normalized['translations']['fr'];

    $recreated_node = $normalizer->denormalize($normalized);
    $this->assertEquals('English Title', $recreated_node->label());
    $this->assertEquals('en', $recreated_node->language()->getId());
    $this->assertTrue($recreated_node->hasTranslation('de'));
    $this->assertTrue($recreated_node->hasTranslation('fr'));
    $this->assertFalse($recreated_node->hasTranslation('es'));
    $this->assertEquals('German Title', $recreated_node->getTranslation('de')->label());
    $this->assertEquals('French Title', $recreated_node->getTranslation('fr')->label());

    // Change the default translation language to a language that isn't known,
    // an available language will be picked from the translations and imported
    // with those values.
    $normalized['_meta']['default_langcode'] = 'it';

    $recreated_node = $normalizer->denormalize($normalized);
    $this->assertEquals('French Title', $recreated_node->label());
    $this->assertEquals('fr', $recreated_node->language()->getId());
    $this->assertTrue($recreated_node->hasTranslation('de'));
    $this->assertTrue($recreated_node->hasTranslation('fr'));
    $this->assertFalse($recreated_node->hasTranslation('it'));
    $this->assertEquals('German Title', $recreated_node->getTranslation('de')->label());

    // Unset all translations, then the entity is created in english.
    unset($normalized['translations']);
    $recreated_node = $normalizer->denormalize($normalized);
    $this->assertEquals('English Title', $recreated_node->label());
    $this->assertEquals('en', $recreated_node->language()->getId());
    $this->assertFalse($recreated_node->hasTranslation('de'));
    $this->assertFalse($recreated_node->hasTranslation('fr'));
    $this->assertFalse($recreated_node->hasTranslation('es'));
  }

}
