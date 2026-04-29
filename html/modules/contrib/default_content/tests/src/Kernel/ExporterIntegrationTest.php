<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

// Workaround to support tests against both Drupal 10.1 and Drupal 11.0.
// @todo Remove once we depend on Drupal 10.2.
if (!trait_exists(EntityReferenceFieldCreationTrait::class)) {
  class_alias('\Drupal\Tests\field\Traits\EntityReferenceTestTrait', EntityReferenceFieldCreationTrait::class);
}

/**
 * Tests export functionality.
 *
 * @coversDefaultClass \Drupal\default_content\Exporter
 * @group default_content
 */
class ExporterIntegrationTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file', 'user'];

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
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
  }

  /**
   * Tests exportContent().
   */
  public function testExportContent() {
    \Drupal::service('module_installer')->install([
      'taxonomy',
      'default_content',
    ]);
    $exporter = \Drupal::service('default_content.exporter');

    $vocabulary = Vocabulary::create(['vid' => 'test']);
    $vocabulary->save();
    $term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'test_name',
      'description' => [
        'value' => 'The description',
        'format' => 'plain_text',
      ],
    ]);
    $term->save();
    $term = Term::load($term->id());

    $exported = $exporter->exportContent('taxonomy_term', $term->id());
    $exported_decoded = Yaml::decode($exported);

    // Assert the meta data and field values.
    $meta = [
      'version' => '1.0',
      'entity_type' => 'taxonomy_term',
      'uuid' => $term->uuid(),
      'bundle' => $term->bundle(),
      'default_langcode' => $term->language()->getId(),
    ];
    $this->assertEquals($meta, $exported_decoded['_meta']);
    $this->assertEquals($term->label(), $exported_decoded['default']['name'][0]['value']);
    $expected_description = [
      [
        'value' => 'The description',
        'format' => 'plain_text',
      ],
    ];
    $this->assertEquals($expected_description, $exported_decoded['default']['description']);

    // Tests export of taxonomy parent field.
    $child_term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'child_name',
      'parent' => $term->id(),
    ]);
    $child_term->save();
    // Make sure parent relation is exported.
    $exported = $exporter->exportContent('taxonomy_term', $child_term->id());
    $exported_decoded = Yaml::decode($exported);
    $this->assertEquals($term->uuid(), $exported_decoded['default']['parent'][0]['entity']);
    $this->assertEquals('taxonomy_term', $exported_decoded['_meta']['depends'][$term->uuid()]);
  }

  /**
   * Tests exportContentWithReferences().
   */
  public function testExportWithReferences() {
    \Drupal::service('module_installer')->install(['node', 'default_content']);
    $exporter = \Drupal::service('default_content.exporter');

    $role = Role::create([
      'id' => 'example_role',
      'label' => 'Example',
    ]);
    $role->save();

    $user = User::create([
      'name' => 'my username',
      'uid' => 2,
      'roles' => $role->id(),
    ]);
    $user->save();
    // Reload the user to get the proper casted values from the DB.
    $user = User::load($user->id());

    $node_type = NodeType::create(['type' => 'test']);
    $node_type->save();
    $node = Node::create([
      'type' => $node_type->id(),
      'title' => 'test node',
      'uid' => $user->id(),
    ]);
    $node->save();
    // Reload the node to get the proper casted values from the DB.
    $node = Node::load($node->id());

    $exported_by_entity_type = $exporter
      ->exportContentWithReferences('node', $node->id());

    // Ensure that the node type is not tried to be exported.
    $this->assertEquals(array_keys($exported_by_entity_type), ['node', 'user']);

    // Ensure the right UUIDs are exported.
    $this->assertEquals([$node->uuid()], array_keys($exported_by_entity_type['node']));
    $this->assertEquals([$user->uuid()], array_keys($exported_by_entity_type['user']));

    // Compare the actual serialized data.
    $meta = [
      'version' => '1.0',
      'entity_type' => 'node',
      'uuid' => $node->uuid(),
      'bundle' => $node->bundle(),
      'default_langcode' => $node->language()->getId(),
      'depends' => [
        $user->uuid() => 'user',
      ],
    ];
    $exported_node = Yaml::decode($exported_by_entity_type['node'][$node->uuid()]);
    $this->assertEquals($meta, $exported_node['_meta']);
    $this->assertEquals($node->label(), $exported_node['default']['title'][0]['value']);

    $meta = [
      'version' => '1.0',
      'entity_type' => 'user',
      'uuid' => $user->uuid(),
      'default_langcode' => $node->language()->getId(),
    ];
    $exported_user = Yaml::decode($exported_by_entity_type['user'][$user->uuid()]);
    $this->assertEquals($meta, $exported_user['_meta']);
    $this->assertEquals($user->label(), $exported_user['default']['name'][0]['value']);
    $this->assertEquals($role->id(), $exported_user['default']['roles'][0]['target_id']);

    // Ensure no recursion on export.
    $field_name = 'field_test_self_ref';
    $this->createEntityReferenceField('node', $node_type->id(), $field_name, 'Self reference field', 'node');

    $node1 = Node::create(['type' => $node_type->id(), 'title' => 'ref 1->3']);
    $node1->save();
    $node2 = Node::create([
      'type' => $node_type->id(),
      'title' => 'ref 2->1',
      $field_name => $node1->id(),
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => $node_type->id(),
      'title' => 'ref 3->2',
      $field_name => $node2->id(),
    ]);
    $node3->save();
    // Loop reference.
    $node1->{$field_name}->target_id = $node3->id();
    $node1->save();
    $exported_by_entity_type = $exporter
      ->exportContentWithReferences('node', $node3->id());
    // Ensure all 3 nodes are exported.
    $this->assertEquals(3, count($exported_by_entity_type['node']));
  }

  /**
   * Tests exportModuleContent().
   */
  public function testModuleExport() {
    \Drupal::service('module_installer')->install([
      'node',
      'default_content',
      'default_content_export_test',
    ]);

    $test_uuid = '0e45d92f-1919-47cd-8b60-964a8a761292';
    $node_type = NodeType::create(['type' => 'test']);
    $node_type->save();

    $user = User::create([
      'name' => 'owner',
    ]);
    $user->save();

    $node = Node::create([
      'type' => $node_type->id(),
      'title' => 'test node',
      'uid' => $user->id(),
      'uuid' => $test_uuid,
    ]);
    $node->save();
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($node->id());
    $expected_node = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => 'node',
        'uuid' => $test_uuid,
        'bundle' => 'test',
        'default_langcode' => 'en',
      ],
      'default' => [
        'revision_uid' => [
          0 => [
            'target_id' => $node->getOwner()->id(),
          ],
        ],
        'status' => [
          0 => [
            'value' => TRUE,
          ],
        ],
        'uid' => [
          0 => [
            'target_id' => $node->getOwner()->id(),
          ],
        ],
        'title' => [
          0 => [
            'value' => 'test node',
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
    ];

    $content = \Drupal::service('default_content.exporter')
      ->exportModuleContent('default_content_export_test');
    $this->assertEquals($expected_node, Yaml::decode($content['node'][$test_uuid]));
  }

  /**
   * Tests exportModuleContent()
   */
  public function testModuleExportException() {
    \Drupal::service('module_installer')->install([
      'node',
      'default_content',
      'default_content_export_test',
    ]);
    \Drupal::service('router.builder')->rebuild();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage(sprintf('Entity "%s" with UUID "%s" does not exist', 'node', '0e45d92f-1919-47cd-8b60-964a8a761292'));

    // Should throw an exception for missing uuid for the testing module.
    \Drupal::service('default_content.exporter')
      ->exportModuleContent('default_content_export_test');
  }

  /**
   * Tests exporting files.
   */
  public function testExportFiles() {
    \Drupal::service('module_installer')->install([
      'default_content',
    ]);

    $test_files = $this->getTestFiles('image');
    $test_file = reset($test_files);

    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => $test_file->uri,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();

    $folder = 'temporary://default_content';
    $exported_by_entity_type = \Drupal::service('default_content.exporter')
      ->exportContentWithReferences('file', $file->id(), $folder);
    $normalized_file = Yaml::decode($exported_by_entity_type['file'][$file->uuid()]);

    $expected = [
      '_meta' => [
        'version' => '1.0',
        'entity_type' => 'file',
        'uuid' => $file->uuid(),
        'default_langcode' => 'en',
      ],
      'default' => [
        'filename' => [
          0 => [
            'value' => $file->getFilename(),
          ],
        ],
        'uri' => [
          0 => [
            'value' => $file->getFileUri(),
          ],
        ],
        'filemime' => [
          0 => [
            'value' => $file->getMimeType(),
          ],
        ],
        'filesize' => [
          0 => [
            'value' => $file->getSize(),
          ],
        ],
        'status' => [
          0 => [
            'value' => TRUE,
          ],
        ],
        'created' => [
          0 => [
            'value' => $file->getCreatedTime(),
          ],
        ],
      ],
    ];

    $this->assertEquals($expected, $normalized_file);

    $this->assertFileExists($folder . '/file/' . $file->uuid() . '.yml');
    $this->assertFileExists($folder . '/file/' . $file->getFilename());
  }

}
