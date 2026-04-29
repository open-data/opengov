<?php

namespace Drupal\default_content;

/**
 * An interface defining a default content exporter.
 */
interface ExporterInterface {

  /**
   * Exports a single entity as importContent expects it.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $entity_id
   *   The entity ID to export.
   * @param string|null $destination
   *   (optional) A file name to write the exported entity into. File entities
   *   also export their files into the same folder.
   *
   * @return string
   *   The rendered export.
   */
  public function exportContent($entity_type_id, $entity_id, $destination = NULL);

  /**
   * Exports a single entity and all its referenced entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $entity_id
   *   The entity ID to export.
   * @param string|null $folder
   *   (optional) A folder to write the exported entities into, grouped by
   *   entity type. File entities also export their files into the same folder.
   *
   * @return string[][]
   *   The serialized entities keyed by entity type and UUID.
   */
  public function exportContentWithReferences($entity_type_id, $entity_id, $folder = NULL);

  /**
   * Exports all of the content defined in a module's info file.
   *
   * @param string $module_name
   *   The name of the module.
   * @param string|null $folder
   *   (optional) A folder to write the exported entities into, grouped by
   *   entity type. File entities also export their files into the same folder.
   *
   * @return string[][]
   *   The serialized entities keyed by entity type and UUID.
   *
   * @throws \InvalidArgumentException
   *   If any UUID is not found.
   */
  public function exportModuleContent($module_name, $folder = NULL);

  /**
   * Exports all content and referenced entities in a module's info file.
   *
   * @param string $module_name
   *   The name of the module.
   * @param string|null $folder
   *   (optional) A folder to write the exported entities into, grouped by
   *   entity type. File entities also export their files into the same folder.
   *
   * @return string[][]
   *   The serialized entities keyed by entity type and UUID.
   *
   * @throws \InvalidArgumentException
   *   If any UUID is not found.
   */
  public function exportModuleContentWithReferences($module_name, $folder = NULL);

}
