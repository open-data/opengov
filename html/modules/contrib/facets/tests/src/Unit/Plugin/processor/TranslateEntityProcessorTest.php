<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\processor\TranslateEntityProcessor;
use Drupal\facets\Result\Result;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class TranslateEntityProcessorTest extends UnitTestCase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock language manager.
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $language = new Language(['langcode' => 'en']);
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    // Mock entity type manager.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Create and set a global container with the language manager and entity
    // type manager.
    $container = new ContainerBuilder();
    $container->set('language_manager', $this->languageManager);
    $container->set('entity_type.manager', $this->entityTypeManager);
    \Drupal::setContainer($container);
  }

  /**
   * Provides mock data for the tests in this class.
   *
   * We provide both supported reference field types.
   *
   * @return array
   *   Field type test data.
   */
  public static function facetDataProvider(): array {
    return [
      'entity_reference' => ['entity_reference'],
      'entity_reference_revision' => ['entity_reference_revision'],
    ];
  }

  /**
   * Tests that node results were correctly changed.
   *
   * @param string $field_type
   *   The field type under test.
   *
   * @dataProvider facetDataProvider
   */
  public function testNodeResultsChanged(string $field_type) {
    [$facet, $results] = $this->createFacetAndResults($field_type);

    // Mock a node and add the label to it.
    $node = $this->createMock(Node::class);
    $node->expects($this->any())
      ->method('label')
      ->willReturn('shaken not stirred');
    $nodes = [
      2 => $node,
    ];
    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($nodes);
    $this->entityTypeManager->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($node_storage);

    // Set expected results.
    $expected_results = [
      ['nid' => 2, 'title' => 'shaken not stirred'],
    ];

    // Without the processor we expect the id to display.
    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['nid'], $results[$key]->getRawValue());
      $this->assertEquals($expected['nid'], $results[$key]->getDisplayValue());
    }

    // With the processor we expect the title to display.
    /** @var \Drupal\facets\Result\ResultInterface[] $filtered_results */
    $processor = new TranslateEntityProcessor([], 'translate_entity', [], $this->languageManager, $this->entityTypeManager);
    $filtered_results = $processor->build($facet, $results);
    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['nid'], $filtered_results[$key]->getRawValue());
      $this->assertEquals($expected['title'], $filtered_results[$key]->getDisplayValue());
    }
  }

  /**
   * Tests that term results were correctly changed.
   *
   * @param string $field_type
   *   The field type under test.
   *
   * @dataProvider facetDataProvider
   */
  public function testTermResultsChanged(string $field_type) {
    [$facet, $results] = $this->createFacetAndResults($field_type);

    // Mock term.
    $term = $this->createMock(Term::class);
    $term->expects($this->once())
      ->method('label')
      ->willReturn('Burrowing owl');
    $terms = [
      2 => $term,
    ];
    $term_storage = $this->createMock(EntityStorageInterface::class);
    $term_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($terms);
    $this->entityTypeManager->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($term_storage);

    // Set expected results.
    $expected_results = [
      ['tid' => 2, 'name' => 'Burrowing owl'],
    ];

    // Without the processor we expect the id to display.
    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['tid'], $results[$key]->getRawValue());
      $this->assertEquals($expected['tid'], $results[$key]->getDisplayValue());
    }

    /** @var \Drupal\facets\Result\ResultInterface[] $filtered_results */
    $processor = new TranslateEntityProcessor([], 'translate_entity', [], $this->languageManager, $this->entityTypeManager);
    $filtered_results = $processor->build($facet, $results);

    // With the processor we expect the title to display.
    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['tid'], $filtered_results[$key]->getRawValue());
      $this->assertEquals($expected['name'], $filtered_results[$key]->getDisplayValue());
    }
  }

  /**
   * Test that deleted entities still in index results doesn't display.
   *
   * @param string $field_type
   *   The field type under test.
   *
   * @dataProvider facetDataProvider
   */
  public function testDeletedEntityResults(string $field_type) {
    [$facet, $results] = $this->createFacetAndResults($field_type);

    // Set original results.
    $term_storage = $this->createMock(EntityStorageInterface::class);
    $term_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn([]);
    $this->entityTypeManager->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($term_storage);

    // Processor should return nothing (and not throw an exception).
    /** @var \Drupal\facets\Result\ResultInterface[] $filtered_results */
    $processor = new TranslateEntityProcessor([], 'translate_entity', [], $this->languageManager, $this->entityTypeManager);
    $filtered_results = $processor->build($facet, $results);
    $this->assertEmpty($filtered_results);
  }

  /**
   * Tests when skip_access_check = FALSE (Default) entities are filtered out.
   */
  public function testAccessCheckEnabledFiltersEntities() {
    $fixture = $this->createFacetTestFixture();
    $facet = $fixture['facet'];
    $entities = $fixture['entities'];
    $results = $fixture['results'];

    // Access handler mock.
    $access_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $this->entityTypeManager->method('getAccessControlHandler')->willReturn($access_handler);

    // Allow 1 & 2, deny 3 & 4.
    $access_handler->method('access')
      ->willReturnCallback(function ($entity) use ($entities) {
        return in_array($entity, [$entities[1], $entities[2]], TRUE)
          ? $this->mockAccessResult(TRUE)
          : $this->mockAccessResult(FALSE);
      });

    $processor = new TranslateEntityProcessor(
      ['skip_access_check' => FALSE],
      'translate_entity',
      [],
      $this->languageManager,
      $this->entityTypeManager
    );

    $filtered = $processor->build($facet, $results);
    $this->assertCount(2, $filtered);
  }

  /**
   * Tests when skip_access_check = TRUE, no entities are filtered out.
   */
  public function testAccessCheckDisabledKeepsAllEntities() {
    $fixture = $this->createFacetTestFixture();
    $facet = $fixture['facet'];
    $results = $fixture['results'];

    // Mock handler denies everything, but skip_access_check=TRUE should bypass.
    $deny_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $deny_handler->method('access')->willReturn($this->mockAccessResult(FALSE));
    $this->entityTypeManager->method('getAccessControlHandler')->willReturn($deny_handler);

    $processor = new TranslateEntityProcessor(
      ['skip_access_check' => TRUE],
      'translate_entity',
      [],
      $this->languageManager,
      $this->entityTypeManager
    );

    $filtered = $processor->build($facet, $results);
    $this->assertCount(4, $filtered);
  }

  /**
   * Helper to create an AccessResultInterface mock.
   *
   * @param bool $allowed
   *   Whether access should be allowed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mock access result.
   */
  protected function mockAccessResult(bool $allowed = TRUE) {
    $result = $this->createMock(AccessResultInterface::class);
    $result->method('isAllowed')->willReturn($allowed);
    return $result;
  }

  /**
   * Creates a complete facet mock, typed-data chain, results, and entity mocks.
   *
   * @return array
   *   Array with keys:
   *   - facet: the facet mock
   *   - entities: array of 4 mocked entities keyed 1–4
   *   - results: array of 4 Result objects
   */
  protected function createFacetTestFixture() {
    // Four mock entities.
    $entity1 = $this->createMock(EntityInterface::class);
    $entity2 = $this->createMock(EntityInterface::class);
    $entity3 = $this->createMock(EntityInterface::class);
    $entity4 = $this->createMock(EntityInterface::class);
    $entities = [
      1 => $entity1,
      2 => $entity2,
      3 => $entity3,
      4 => $entity4,
    ];

    // Mock storage.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($entities);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    // Facet mock.
    $facet = $this->createMock(FacetInterface::class);
    $facet->expects($this->any())->method('addCacheableDependency')->willReturnSelf();
    $facet->method('getFieldIdentifier')->willReturn('testfield');

    // Typed data chain.
    $target_field_definition = $this->createMock(EntityDataDefinition::class);
    $target_field_definition->method('getEntityTypeId')->willReturn('entity_type');

    $property_definition = $this->createMock(DataReferenceDefinitionInterface::class);
    $property_definition->method('getTargetDefinition')->willReturn($target_field_definition);
    $property_definition->method('getDataType')->willReturn('entity_reference');

    $data_definition = $this->createMock(ComplexDataDefinitionInterface::class);
    $data_definition->method('getPropertyDefinition')->willReturn($property_definition);
    $data_definition->method('getPropertyDefinitions')->willReturn([$property_definition]);

    $facet->method('getDataDefinition')->willReturn($data_definition);

    // Result objects.
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
   * Creates a facet and single-result set for a given field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return array
   *   A tuple containing:
   *   - 0: Facet mock.
   *   - 1: Result array.
   */
  protected function createFacetAndResults(string $field_type): array {
    $target_field_definition = $this->createMock(EntityDataDefinition::class);
    $target_field_definition->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn('entity_type');

    $property_definition = $this->createMock(DataReferenceDefinitionInterface::class);
    $property_definition->expects($this->any())
      ->method('getTargetDefinition')
      ->willReturn($target_field_definition);
    $property_definition->expects($this->any())
      ->method('getDataType')
      ->willReturn($field_type);

    $data_definition = $this->createMock(ComplexDataDefinitionInterface::class);
    $data_definition->expects($this->any())
      ->method('getPropertyDefinition')
      ->willReturn($property_definition);
    $data_definition->expects($this->any())
      ->method('getPropertyDefinitions')
      ->willReturn([$property_definition]);

    $facet = $this->createMock(Facet::class);
    $facet->expects($this->any())
      ->method('getDataDefinition')
      ->willReturn($data_definition);
    $facet->expects($this->any())
      ->method('getFieldIdentifier')
      ->willReturn('testfield');

    $results = [new Result($facet, 2, 2, 5)];
    $facet->setResults($results);

    return [$facet, $results];
  }

}
