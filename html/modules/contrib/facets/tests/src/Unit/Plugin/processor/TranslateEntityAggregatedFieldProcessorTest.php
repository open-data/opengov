<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\facets\Plugin\facets\processor\TranslateEntityAggregatedFieldProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for aggregated-field entity translation processor.
 *
 * @group facets
 */
class TranslateEntityAggregatedFieldProcessorTest extends UnitTestCase {

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configManager;

  /**
   * The mocked entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The mocked bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeBundleInfo;

  /**
   * The mocked entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityDefinitionUpdateManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $language = new Language(['langcode' => 'en']);
    $this->languageManager->method('getCurrentLanguage')->willReturn($language);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configManager = $this->createMock(ConfigManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->entityTypeBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
    $this->entityDefinitionUpdateManager = $this->createMock(EntityDefinitionUpdateManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity.definition_update_manager', $this->entityDefinitionUpdateManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests access filtering when skip_access_check is disabled.
   */
  public function testAccessCheckEnabledFiltersEntities(): void {
    $fixture = $this->createFacetTestFixture();
    $facet = $fixture['facet'];
    $results = $fixture['results'];
    $entities = $fixture['entities'];

    $access_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $this->entityTypeManager->method('getAccessControlHandler')->willReturn($access_handler);
    $access_handler->method('access')
      ->willReturnCallback(function ($entity) use ($entities) {
        return in_array($entity, [$entities[1], $entities[2]], TRUE)
          ? $this->mockAccessResult(TRUE)
          : $this->mockAccessResult(FALSE);
      });

    $processor = $this->createProcessor(['skip_access_check' => FALSE]);
    $processed = $processor->build($facet, $results);

    $this->assertSame('Entity 1', $processed[0]->getDisplayValue());
    $this->assertSame('Entity 2', $processed[1]->getDisplayValue());
    $this->assertSame(3, $processed[2]->getDisplayValue());
    $this->assertSame(4, $processed[3]->getDisplayValue());
  }

  /**
   * Tests access bypassing when skip_access_check is enabled.
   */
  public function testAccessCheckDisabledKeepsAllEntities(): void {
    $fixture = $this->createFacetTestFixture();
    $facet = $fixture['facet'];
    $results = $fixture['results'];

    $this->entityTypeManager->expects($this->never())
      ->method('getAccessControlHandler');

    $processor = $this->createProcessor(['skip_access_check' => TRUE]);
    $processed = $processor->build($facet, $results);

    $this->assertSame('Entity 1', $processed[0]->getDisplayValue());
    $this->assertSame('Entity 2', $processed[1]->getDisplayValue());
    $this->assertSame('Entity 3', $processed[2]->getDisplayValue());
    $this->assertSame('Entity 4', $processed[3]->getDisplayValue());
  }

  /**
   * Creates a processor instance.
   *
   * @param array $configuration
   *   Plugin configuration.
   *
   * @return \Drupal\facets\Plugin\facets\processor\TranslateEntityAggregatedFieldProcessor
   *   The processor.
   */
  protected function createProcessor(array $configuration): TranslateEntityAggregatedFieldProcessor {
    return new TranslateEntityAggregatedFieldProcessor(
      $configuration,
      'translate_entity_aggregated_fields',
      [],
      $this->languageManager,
      $this->entityTypeManager,
      $this->configManager,
      $this->entityFieldManager,
      $this->entityTypeBundleInfo,
      $this->entityDefinitionUpdateManager
    );
  }

  /**
   * Creates facet, results, and entity mocks for aggregated-field tests.
   *
   * @return array
   *   Array with keys:
   *   - facet: the facet mock
   *   - entities: array of mocked entities keyed by raw value
   *   - results: array of Result objects
   */
  protected function createFacetTestFixture(): array {
    $field_storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_storage->method('getType')->willReturn('entity_reference');
    $field_storage->method('getSettings')->willReturn(['target_type' => 'user']);

    $this->entityDefinitionUpdateManager
      ->method('getFieldStorageDefinition')
      ->willReturn($field_storage);

    $this->entityTypeBundleInfo->method('getBundleInfo')->willReturn([]);

    $entities = [];
    for ($i = 1; $i <= 4; $i++) {
      $entity = $this->createMock(EntityInterface::class);
      $entity->method('label')->willReturn("Entity $i");
      $entities[$i] = $entity;
    }

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($entities);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $index_field = new class {

      /**
       * Returns the aggregated field configuration.
       */
      public function getConfiguration(): array {
        return ['fields' => ['entity:node/uid']];
      }

    };

    $index = new class($index_field) {

      /**
       * The index field mock.
       *
       * @var object
       */
      protected $field;

      /**
       * Constructs a lightweight index stub.
       *
       * @param object $field
       *   The field stub.
       */
      public function __construct($field) {
        $this->field = $field;
      }

      /**
       * Returns a field object by identifier.
       *
       * @param string $field_identifier
       *   The field identifier.
       *
       * @return object
       *   The field stub.
       */
      public function getField($field_identifier) {
        return $this->field;
      }

    };

    $facet_source = $this->getMockBuilder(SearchApiDisplay::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getIndex'])
      ->getMock();
    $facet_source->method('getIndex')->willReturn($index);

    $facet = $this->createMock(FacetInterface::class);
    $facet->method('getFieldIdentifier')->willReturn('aggregated_field');
    $facet->method('getFacetSource')->willReturn($facet_source);
    $facet->method('addCacheableDependency')->willReturnSelf();

    $results = [
      new Result($facet, 1, 1, 1),
      new Result($facet, 2, 2, 2),
      new Result($facet, 3, 3, 3),
      new Result($facet, 4, 4, 4),
    ];

    return [
      'facet' => $facet,
      'entities' => $entities,
      'results' => $results,
    ];
  }

  /**
   * Creates an access-result mock.
   *
   * @param bool $allowed
   *   Whether access is granted.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The access result mock.
   */
  protected function mockAccessResult(bool $allowed): AccessResultInterface {
    $result = $this->createMock(AccessResultInterface::class);
    $result->method('isAllowed')->willReturn($allowed);
    return $result;
  }

}
