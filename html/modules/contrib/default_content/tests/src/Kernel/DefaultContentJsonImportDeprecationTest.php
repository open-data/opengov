<?php

namespace Drupal\Tests\default_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * Tests deprecation of HAL-JSON serialized files import.
 *
 * @group default_content
 * @group legacy
 */
class DefaultContentJsonImportDeprecationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'default_content',
    'field',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['node']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');

    $this->createContentType(['type' => 'page']);
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();

    // Create user 1 and 2 with the correct UUID.
    $this->createUser([], 'user1', FALSE, ['uid' => 1]);
    $this->createUser([], 'user2', FALSE, [
      'uid' => 2,
      'uuid' => 'ab301be5-7017-4ff8-b2d3-09dc0a30bd43',
    ]);
  }

  /**
   * Tests deprecation of HAL-JSON serialized files import.
   */
  public function testImportWithHalModuleInstalled(): void {
    \Drupal::service('module_installer')->install(['hal', 'serialization']);
    $logger = new BufferingLogger();
    $this->container->get('logger.factory')->addLogger($logger);
    $this->container->get('default_content.importer')->importContent('default_content_test');

    $logs = $logger->cleanLogs();
    $this->assertEquals('Importing entities from files serialized with hal_json is deprecated in default_content:2.0.0-alpha2 and is removed from default_content:3.0.0. The following files were serialized using hal_json serialization: @files. Import all entities and re-export them as YAML files. See https://www.drupal.org/node/3296226', $logs[0][1]);
    $this->assertStringContainsString('default_content_test/content/node/imported.json', $logs[0][2]['@files']);
    $this->assertStringContainsString('default_content_test/content/node/user_1.json', $logs[0][2]['@files']);
  }
  /**
   * Tests deprecation of HAL-JSON serialized files import.
   */
  public function testImportWithoutHalModuleInstalled(): void {
    \Drupal::service('module_installer')->install(['serialization']);
    $this->expectExceptionMessage('To import hal_json files, the hal module must be enabled. This is deprecated and will be removed in default_content:3.0.0. See https://www.drupal.org/node/3296226');
    $this->container->get('default_content.importer')->importContent('default_content_test');
  }

}
