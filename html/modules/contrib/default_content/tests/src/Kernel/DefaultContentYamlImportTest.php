<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Test import of default content.
 *
 * @group default_content
 */
class DefaultContentYamlImportTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'taxonomy',
    'node',
    'text',
    'filter',
    'field',
    'default_content',
    'serialization',
    'system',
    'user',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    $this->installConfig(['system']);

    // Create user 1 and 2 with the correct UUID.
    User::create([
      'uid' => 1,
      'name' => 'user1',
    ])->save();
    User::create([
      'uid' => 2,
      'uuid' => 'ab301be5-7017-4ff8-b2d3-09dc0a30bd43',
      'name' => 'User 2',
      'mail' => 'user2@example.com',
      'status' => TRUE,
    ])->save();
    $this->installConfig(['node']);
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Test importing default content.
   */
  public function testImport() {

    // Simulate an existing target file.
    file_put_contents('public://test-file.txt', 'exists');

    // Enable the module and import the content.
    \Drupal::service('module_installer')->install(['default_content_test_yaml'], TRUE);

    $this->doPostInstallTests();
  }

  /**
   * Test importing default content via ConfigImporter.
   */
  public function testImportViaConfigImporter() {

    // Simulate an existing target file.
    file_put_contents('public://test-file.txt', 'exists');

    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);

    // Enable the module using the ConfigImporter.
    $extensions = $sync->read('core.extension');
    $extensions['module']['default_content_test_yaml'] = 0;
    $extensions['module'] = module_config_sort($extensions['module']);
    $sync->write('core.extension', $extensions);
    // Slightly hacky but we need the config from the test module too.
    $module_storage = new FileStorage(\Drupal::service('extension.list.module')->getPath('default_content_test_yaml') . '/config/install');
    foreach ($module_storage->listAll() as $name) {
      $sync->write($name, $module_storage->read($name));
    }
    $this->configImporter()->import();

    $this->doPostInstallTests();
  }

  /**
   * Makes assertions post the install of the default_content_test module.
   */
  protected function doPostInstallTests() {
    // Ensure the content contained in the default_content_test module has been
    // created correctly.
    $node = $this->getNodeByTitle('Imported node');
    $this->assertEquals('Crikey it works!', $node->get('body')->value);
    $this->assertEquals('page', $node->getType());
    $this->assertSame('2', $node->getOwnerId(), 'The node created is owned by user 2');

    $node = $this->getNodeByTitle('Imported node with owned by user that does not exist');
    $this->assertSame('1', $node->getOwnerId(), 'The node created is owned by user 1');

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple();
    $term = reset($terms);
    $this->assertInstanceOf(TermInterface::class, $term);
    $this->assertEquals('A tag', $term->label());
    $this->assertEquals($term->id(), $node->get('field_tags')->target_id);

    // Assert the files, since a file already existed at that location, one has
    // been renamed and the URI adjusted.
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['filename' => 'test-file.txt']);
    $this->assertCount(1, $files);
    /** @var \Drupal\file\FileInterface $file */
    $file = reset($files);
    $this->assertEquals('public://test-file_0.txt', $file->getFileUri());
    $this->assertFileExists($file->getFileUri());
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['filename' => 'test-file1.txt']);
    $this->assertCount(1, $files);
    /** @var \Drupal\file\FileInterface $file */
    $file = reset($files);
    $this->assertEquals('public://example/test-file1.txt', $file->getFileUri());
    $this->assertFileExists($file->getFileUri());
  }

}
