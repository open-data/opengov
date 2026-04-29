<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\menu_link_content\Entity\MenuLinkContent;
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
class MenuLinkContentNormalizerTest extends KernelTestBase {

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
    'link',
    'menu_link_content',
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
    $this->installEntitySchema('menu_link_content');

    // Create a node type with a paragraphs field.
    NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ])->save();
  }

  /**
   * Tests menu_link_content entities.
   */
  public function testMenuLinks() {

    /** @var \Drupal\node\NodeInterface $referenced_node */
    $referenced_node = Node::create([
      'type' => 'page',
      'title' => 'Referenced node',
    ]);
    $referenced_node->save();

    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $link */
    $link = MenuLinkContent::create([
      'title' => 'Parent menu link',
      'link' => 'entity:node/' . $referenced_node->id(),
    ]);
    $link->save();

    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $child_link */
    $child_link = MenuLinkContent::create([
      'title' => 'Child menu link',
      'parent' => 'menu_link_content:' . $link->uuid(),
      'link' => [
        'uri' => 'https://www.example.org',
        'options' => [
          'attributes' => [
            'target' => '_blank',
          ],
        ],
      ],
    ]);
    $child_link->save();

    /** @var \Drupal\default_content\Normalizer\ContentEntityNormalizerInterface $normalizer */
    $normalizer = \Drupal::service('default_content.content_entity_normalizer');

    $normalized = $normalizer->normalize($link);

    $expected = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => 'menu_link_content',
        'uuid' => $link->uuid(),
        'bundle' => 'menu_link_content',
        'default_langcode' => 'en',
        'depends' => [
          $referenced_node->uuid() => 'node',
        ],
      ],
      'default' => [
        'enabled' => [
          0 => [
            'value' => TRUE,
          ],
        ],
        'title' => [
          0 => [
            'value' => 'Parent menu link',
          ],
        ],
        'menu_name' => [
          0 => [
            'value' => 'tools',
          ],
        ],
        'link' => [
          0 => [
            'target_uuid' => $referenced_node->uuid(),
            'title' => '',
            'options' => [],
          ],
        ],
        'external' => [
          0 => [
            'value' => FALSE,
          ],
        ],
        'rediscover' => [
          0 => [
            'value' => FALSE,
          ],
        ],
        'weight' => [
          0 => [
            'value' => 0,
          ],
        ],
        'expanded' => [
          0 => [
            'value' => FALSE,
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $normalized);

    $normalized_child = $normalizer->normalize($child_link);

    $expected_child = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => 'menu_link_content',
        'uuid' => $child_link->uuid(),
        'bundle' => 'menu_link_content',
        'default_langcode' => 'en',
        'depends' => [
          $link->uuid() => 'menu_link_content',
        ],
      ],
      'default' => [
        'enabled' => [
          0 => [
            'value' => TRUE,
          ],
        ],
        'title' => [
          0 => [
            'value' => 'Child menu link',
          ],
        ],
        'menu_name' => [
          0 => [
            'value' => 'tools',
          ],
        ],
        'link' => [
          0 => [
            'uri' => 'https://www.example.org',
            'title' => '',
            'options' => [
              'attributes' => [
                'target' => '_blank',
              ],
            ],
          ],
        ],
        'external' => [
          0 => [
            'value' => FALSE,
          ],
        ],
        'rediscover' => [
          0 => [
            'value' => FALSE,
          ],
        ],
        'weight' => [
          0 => [
            'value' => 0,
          ],
        ],
        'expanded' => [
          0 => [
            'value' => FALSE,
          ],
        ],
        'parent' => [
          0 => [
            'value' => $child_link->getParentId(),
          ],
        ],
      ],
    ];
    $this->assertEquals($expected_child, $normalized_child);

    // Delete the link and referenced node and recreate them.
    $normalized_node = $normalizer->normalize($referenced_node);
    $child_link->delete();
    $link->delete();
    $referenced_node->delete();

    $recreated_node = $normalizer->denormalize($normalized_node);
    $recreated_node->save();
    $this->assertNotEquals($referenced_node->id(), $recreated_node->id());

    $recreated_link = $normalizer->denormalize($normalized);
    $recreated_link->save();

    $this->assertEquals('entity:node/' . $recreated_node->id(), $recreated_link->get('link')->uri);
  }

}
