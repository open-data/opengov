<?php

namespace Drupal\Tests\search_api\Kernel\Index;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests whether the storage of search indexes works correctly.
 *
 * @group search_api
 */
#[RunTestsInSeparateProcesses]
class IndexStorageTest extends KernelTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  protected static $modules = ['search_api', 'user', 'system'];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests all CRUD operations as a queue of operations.
   */
  public function testIndexCrud() {
    $this->assertInstanceOf(ConfigEntityStorage::class, $this->entityTypeManager->getStorage('search_api_index'), 'The Search API Index storage controller is loaded.');

    $index = $this->indexCreate();
    $this->indexLoad($index);
    $this->indexDelete($index);
  }

  /**
   * Tests whether creating an index works correctly.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The newly created search index.
   */
  protected function indexCreate() {
    $index_data = [
      'id' => 'test',
      'name' => 'Index test name',
    ];

    $index = $this->entityTypeManager->getStorage('search_api_index')->create($index_data);
    $this->assertInstanceOf(IndexInterface::class, $index, 'The newly created entity is a search index.');
    $index->save();

    return $index;
  }

  /**
   * Tests whether loading an index works correctly.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index used for the test.
   */
  protected function indexLoad(IndexInterface $index) {
    $loaded_index = $this->entityTypeManager->getStorage('search_api_index')->load($index->id());
    $this->assertSame($index->label(), $loaded_index->label());
  }

  /**
   * Tests whether deleting an index works correctly.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index used for the test.
   */
  protected function indexDelete(IndexInterface $index) {
    $this->entityTypeManager->getStorage('search_api_index')->delete([$index]);
    $loaded_index = $this->entityTypeManager->getStorage('search_api_index')->load($index->id());
    $this->assertNull($loaded_index);
  }

}
