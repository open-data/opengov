<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
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
 * @requires module paragraphs
 * @coversDefaultClass \Drupal\default_content\Normalizer\ContentEntityNormalizer
 * @group default_content
 */
class ParagraphNormalizerTest extends KernelTestBase {

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
    'paragraphs',
    'entity_reference_revisions',
    'node',
    'file',
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
    $this->installEntitySchema('paragraph');

    // Create a node type with a paragraphs field.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_paragraphs',
      'type' => 'entity_reference_revisions',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Paragraphs',
    ])->save();

    // Create a paragraph type with a nested paragraph field and an entity
    // reference field to nodes.
    ParagraphsType::create([
      'id' => 'paragraph_type',
      'label' => 'paragraph_type',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_paragraphs',
      'type' => 'entity_reference_revisions',
      'entity_type' => 'paragraph',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'paragraph_type',
      'label' => 'Paragraphs',
    ])->save();

    $this->createEntityReferenceField('paragraph', 'paragraph_type', 'field_node_reference', 'Node', 'node');
  }

  /**
   * Tests exportContent().
   */
  public function testEmbeddedParagraphs() {

    $referenced_node = Node::create([
      'type' => 'page',
      'title' => 'Referenced node',
    ]);
    $referenced_node->save();

    $child_a = Paragraph::create([
      'type' => 'paragraph_type',
      'field_node_reference' => $referenced_node,
    ]);

    $child_b = Paragraph::create([
      'type' => 'paragraph_type',
      'field_paragraphs' => $child_a,
      'behavior_settings' => serialize(['this is' => 'a behavior setting']),
    ]);

    $paragraph = Paragraph::create([
      'type' => 'paragraph_type',
      'field_paragraphs' => $child_b,
    ]);

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'page',
      'title' => 'Main node',
      'field_paragraphs' => [
        $paragraph,
      ],
    ]);
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
        'depends' => [
          $referenced_node->uuid() => 'node',
        ],
      ],
      'default' => [
        'revision_uid' => [
          0 => [
            'target_id' => 0,
          ],
        ],
        'uid' => [
          0 => [
            'target_id' => 0,
          ],
        ],
        'status' => [
          0 => [
            'value' => TRUE,
          ],
        ],
        'title' => [
          0 => [
            'value' => 'Main node',
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
        'field_paragraphs' => [
          0 => [
            'entity' => [
              '_meta' => [
                'version' => '1.0',
                'entity_type' => 'paragraph',
                'uuid' => $paragraph->uuid(),
                'bundle' => 'paragraph_type',
                'default_langcode' => 'en',
              ],
              'default' => [
                'status' => [
                  0 => [
                    'value' => TRUE,
                  ],
                ],
                'created' => [
                  0 => [
                    'value' => $paragraph->getCreatedTime(),
                  ],
                ],
                'behavior_settings' => [
                  0 => [
                    'value' => [],
                  ],
                ],
                'field_paragraphs' => [
                  0 => [
                    'entity' => [
                      '_meta' => [
                        'version' => '1.0',
                        'entity_type' => 'paragraph',
                        'uuid' => $child_b->uuid(),
                        'bundle' => 'paragraph_type',
                        'default_langcode' => 'en',
                      ],
                      'default' => [
                        'status' => [
                          0 => [
                            'value' => TRUE,
                          ],
                        ],
                        'created' => [
                          0 => [
                            'value' => $child_b->getCreatedTime(),
                          ],
                        ],
                        'behavior_settings' => [
                          0 => [
                            'value' => ['this is' => 'a behavior setting'],
                          ],
                        ],
                        'field_paragraphs' => [
                          0 => [
                            'entity' => [
                              '_meta' => [
                                'version' => '1.0',
                                'entity_type' => 'paragraph',
                                'uuid' => $child_a->uuid(),
                                'bundle' => 'paragraph_type',
                                'default_langcode' => 'en',
                              ],
                              'default' => [
                                'status' => [
                                  0 => [
                                    'value' => TRUE,
                                  ],
                                ],
                                'created' => [
                                  0 => [
                                    'value' => $child_a->getCreatedTime(),
                                  ],
                                ],
                                'behavior_settings' => [
                                  0 => [
                                    'value' => [],
                                  ],
                                ],
                                'field_node_reference' => [
                                  0 => [
                                    'entity' => $referenced_node->uuid(),
                                  ],
                                ],
                              ],
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $normalized);

    // Delete the node and let entity reference revisions purge the referenced
    // paragraphs.
    $node->delete();
    \Drupal::service('cron')->run();

    $this->assertNull(Paragraph::load($paragraph->id()));
    $this->assertNull(Paragraph::load($child_a->id()));
    $this->assertNull(Paragraph::load($child_b->id()));

    // Recreate the node and embedded paragraphs, verify their structure.
    $recreated_node = $normalizer->denormalize($normalized);
    $recreated_node->save();

    $this->assertEquals('Main node', $recreated_node->label());
    $this->assertEquals($node->uuid(), $recreated_node->uuid());
    $this->assertNotEquals($node->id(), $recreated_node->id());

    $recreated_paragraph = $recreated_node->get('field_paragraphs')->entity;
    $this->assertEquals($paragraph->uuid(), $recreated_paragraph->uuid());
    $this->assertEquals($paragraph->getCreatedTime(), $recreated_paragraph->getCreatedTime());

    $recreated_child_b = $recreated_paragraph->get('field_paragraphs')->entity;
    $this->assertEquals($child_b->uuid(), $recreated_child_b->uuid());
    $this->assertEquals($child_b->getCreatedTime(), $recreated_child_b->getCreatedTime());

    $recreated_child_a = $recreated_child_b->get('field_paragraphs')->entity;
    $this->assertEquals($child_a->uuid(), $recreated_child_a->uuid());
    $this->assertEquals($child_a->getCreatedTime(), $recreated_child_a->getCreatedTime());
    $this->assertEquals($referenced_node->id(), $recreated_child_a->get('field_node_reference')->target_id);

    // Test that the exporter does not include paragraphs but includes entities
    // referenced by them.
    /** @var \Drupal\default_content\ExporterInterface $exporter */
    $exporter = \Drupal::service('default_content.exporter');

    $by_entity_type = $exporter->exportContentWithReferences('node', $recreated_node->id());
    $this->assertArrayHasKey($recreated_node->uuid(), $by_entity_type['node']);
    $this->assertArrayHasKey($referenced_node->uuid(), $by_entity_type['node']);
    $this->assertArrayNotHasKey('paragraph', $by_entity_type);
  }

}
