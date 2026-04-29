<?php

namespace Drupal\default_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ExportEvent;
use Drupal\default_content\Normalizer\ContentEntityNormalizerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A service for handling import of default content.
 *
 * @todo throw useful exceptions
 */
class Exporter implements ExporterInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The info file parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The content file storage.
   *
   * @var \Drupal\default_content\ContentFileStorageInterface
   */
  protected $contentFileStorage;

  /**
   * The YAML normalizer.
   *
   * @var \Drupal\default_content\Normalizer\ContentEntityNormalizer
   */
  protected $contentEntityNormalizer;

  /**
   * Constructs the default content manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   * @param \Drupal\default_content\ContentFileStorageInterface $content_file_storage
   *   The content file storage service.
   * @param \Drupal\default_content\Normalizer\ContentEntityNormalizerInterface $content_entity_normalizer
   *   The content entity normalizer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, InfoParserInterface $info_parser, ContentFileStorageInterface $content_file_storage, ContentEntityNormalizerInterface $content_entity_normalizer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->infoParser = $info_parser;
    $this->contentFileStorage = $content_file_storage;
    $this->contentEntityNormalizer = $content_entity_normalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContent($entity_type_id, $entity_id, $destination = NULL) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException(sprintf('Entity "%s" with ID "%s" does not exist', $entity_type_id, $entity_id));
    }
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException(sprintf('Entity "%s" with ID "%s" is not a content entity', $entity_type_id, $entity_id));
    }

    $normalized = $this->contentEntityNormalizer->normalize($entity);
    $return = Yaml::encode($normalized);
    if ($destination) {
      $folder = dirname(dirname($destination));
      $this->contentFileStorage->writeEntity($folder, $return, $entity, basename($destination));
    }
    $this->eventDispatcher->dispatch(new ExportEvent($entity), DefaultContentEvents::EXPORT);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContentWithReferences($entity_type_id, $entity_id, $folder = NULL) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException(sprintf('Entity "%s" with ID "%s" does not exist', $entity_type_id, $entity_id));
    }
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException(sprintf('Entity "%s" with ID "%s" is not a content entity', $entity_type_id, $entity_id));
    }

    $entities = [$entity->uuid() => $entity];
    $entities = $this->getEntityReferencesRecursive($entity, 0, $entities);
    // Serialize all entities and key them by entity TYPE and uuid.
    $serialized_entities_per_type = [];
    foreach ($entities as $entity) {
      $normalized = $this->contentEntityNormalizer->normalize($entity);
      $encoded = Yaml::encode($normalized);
      $serialized_entities_per_type[$entity->getEntityTypeId()][$entity->uuid()] = $encoded;

      if ($folder) {
        $this->contentFileStorage->writeEntity($folder, $encoded, $entity);
      }
    }

    return $serialized_entities_per_type;
  }

  /**
   * {@inheritdoc}
   */
  public function exportModuleContent($module_name, $folder = NULL) {
    $info_file = $this->moduleHandler->getModule($module_name)->getPathname();
    $info = $this->infoParser->parse($info_file);
    $exported_content = [];
    if (empty($info['default_content'])) {
      return $exported_content;
    }
    foreach ($info['default_content'] as $entity_type => $uuids) {
      foreach ($uuids as $uuid) {
        $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
        if (!$entity) {
          throw new \InvalidArgumentException(sprintf('Entity "%s" with UUID "%s" does not exist', $entity_type, $uuid));
        }
        $exported_content[$entity_type][$uuid] = $this->exportContent($entity_type, $entity->id());

        if ($folder) {
          $this->contentFileStorage->writeEntity($folder, $exported_content[$entity_type][$uuid], $entity);
        }
      }
    }
    return $exported_content;
  }

  /**
   * {@inheritdoc}
   */
  public function exportModuleContentWithReferences($module_name, $folder = NULL) {
    $info_file = $this->moduleHandler->getModule($module_name)->getPathname();
    $info = $this->infoParser->parse($info_file);
    $exported_content = [];
    if (empty($info['default_content'])) {
      return $exported_content;
    }
    foreach ($info['default_content'] as $entity_type => $uuids) {
      foreach ($uuids as $uuid) {
        $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
        if (!$entity) {
          throw new \InvalidArgumentException(sprintf('Entity "%s" with UUID "%s" does not exist', $entity_type, $uuid));
        }
        $exported_content_with_references = $this->exportContentWithReferences($entity_type, $entity->id(), $folder);
        foreach ($exported_content_with_references as $ref_entity_type => $entities) {
          foreach ($entities as $ref_uuid => $entity) {
            $exported_content[$ref_entity_type][$ref_uuid] = $entity;
          }
        }
      }
    }
    return $exported_content;
  }

  /**
   * Returns all referenced entities of an entity.
   *
   * This method is also recursive to support use-cases like a node -> media
   * -> file.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param int $depth
   *   Guard against infinite recursion.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $indexed_dependencies
   *   Previously discovered dependencies.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Keyed array of entities indexed by entity type and ID.
   */
  protected function getEntityReferencesRecursive(ContentEntityInterface $entity, $depth = 0, array &$indexed_dependencies = []) {
    $entity_dependencies = $entity->referencedEntities();

    foreach ($entity_dependencies as $dependent_entity) {
      // Config entities should not be exported but rather provided by default
      // config.
      if (!($dependent_entity instanceof ContentEntityInterface)) {
        continue;
      }

      // Do not export user 0 or 1.
      if ($dependent_entity instanceof UserInterface && \in_array($dependent_entity->id(), [0, 1])) {
        continue;
      }

      // Using UUID to keep dependencies unique to prevent recursion.
      $key = $dependent_entity->uuid();
      if (isset($indexed_dependencies[$key])) {
        // Do not add already indexed dependencies.
        continue;
      }

      // Do not export composite entity types directly but include their
      // children.
      if (!$dependent_entity->getEntityType()->get('entity_revision_parent_type_field')) {
        $indexed_dependencies[$key] = $dependent_entity;
      }

      // Build in some support against infinite recursion.
      if ($depth < 6) {
        // @todo Make $depth configurable.
        $indexed_dependencies += $this->getEntityReferencesRecursive($dependent_entity, $depth + 1, $indexed_dependencies);
      }
    }

    return $indexed_dependencies;
  }

}
