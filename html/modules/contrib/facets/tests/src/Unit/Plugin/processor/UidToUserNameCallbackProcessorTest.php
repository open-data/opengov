<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Plugin\facets\processor\UidToUserNameCallbackProcessor;
use Drupal\facets\Result\Result;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class UidToUserNameCallbackProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\SortProcessorInterface
   */
  protected $processor;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->processor = new UidToUserNameCallbackProcessor([], 'uid_to_username_callback', []);
  }

  /**
   * Tests that results were correctly changed.
   */
  public function testResultsChanged() {
    $user_storage = $this->createMock(EntityStorageInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->willReturn('user');
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($user_storage);

    $user1 = $this->createMock(AccountInterface::class);
    $user1->method('getDisplayName')
      ->willReturn('Admin');

    $user_storage->method('load')
      ->willReturn($user1);

    $container = new ContainerBuilder();
    $container->set('entity_type.repository', $entity_repository);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    $facet = new Facet([], 'facets_facet');
    $original_results = [
      new Result($facet, 1, 1, 5),
    ];

    $facet->setResults($original_results);

    $expected_results = [
      ['uid' => 1, 'name' => 'Admin'],
    ];

    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['uid'], $original_results[$key]->getRawValue());
      $this->assertEquals($expected['uid'], $original_results[$key]->getDisplayValue());
    }

    $filtered_results = $this->processor->build($facet, $original_results);

    foreach ($expected_results as $key => $expected) {
      $this->assertEquals($expected['uid'], $filtered_results[$key]->getRawValue());
      $this->assertEquals($expected['name'], $filtered_results[$key]->getDisplayValue());
    }
  }

  /**
   * Tests that deleted entity results were correctly handled.
   */
  public function testDeletedEntityResults() {
    $user_storage = $this->createMock(EntityStorageInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_repository->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->willReturn('user');
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($user_storage);

    $user_storage->method('load')
      ->willReturn(NULL);

    $container = new ContainerBuilder();
    $container->set('entity_type.repository', $entity_repository);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    $facet = new Facet([], 'facets_facet');
    $original_results = [
      new Result($facet, 1, 1, 5),
    ];

    $facet->setResults($original_results);

    $filtered_results = $this->processor->build($facet, $original_results);

    $this->assertEmpty($filtered_results);
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals([], $config);
  }

  /**
   * Tests that a facet on a user reference field is supported.
   */
  public function testSupportsFacet() {
    $this->assertTrue($this->processor->supportsFacet($this->mockFacet('user')));
    $this->assertFalse($this->processor->supportsFacet($this->mockFacet('node')));
  }

  /**
   * Creates a facet mock for a reference field to the given entity type.
   *
   * @param string $entity_type_id
   *   The referenced entity type ID.
   *
   * @return \Drupal\facets\FacetInterface
   *   The facet mock.
   */
  protected function mockFacet(string $entity_type_id) {
    $property_definition = $this->createMock(DataReferenceDefinitionInterface::class);
    $property_definition->expects($this->any())
      ->method('getDataType')
      ->willReturn('entity_reference');
    $property_definition->expects($this->any())
      ->method('getTargetDefinition')
      ->willReturn((new EntityDataDefinition())->setEntityTypeId($entity_type_id));

    $data_definition = $this->createMock(ComplexDataDefinitionInterface::class);
    $data_definition->expects($this->any())
      ->method('getPropertyDefinitions')
      ->willReturn([$property_definition]);

    $facet = $this->createMock(Facet::class);
    $facet->expects($this->any())
      ->method('getDataDefinition')
      ->willReturn($data_definition);

    return $facet;
  }

}
