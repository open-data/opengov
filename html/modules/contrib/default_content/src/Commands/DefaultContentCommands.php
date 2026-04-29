<?php

namespace Drupal\default_content\Commands;

use Drupal\default_content\ExporterInterface;
use Drush\Commands\DrushCommands;

/**
 * Class DefaultContentCommands.
 *
 * @package Drupal\default_content
 */
class DefaultContentCommands extends DrushCommands {

  /**
   * The default content exporter.
   *
   * @var \Drupal\default_content\ExporterInterface
   */
  protected $defaultContentExporter;

  /**
   * SimplesitemapController constructor.
   *
   * @param \Drupal\default_content\ExporterInterface $default_content_exporter
   *   The default content exporter.
   */
  public function __construct(ExporterInterface $default_content_exporter) {
    $this->defaultContentExporter = $default_content_exporter;
  }

  /**
   * Exports a single entity.
   *
   * @param string $entity_type_id
   *   The entity type to export.
   * @param int $entity_id
   *   The ID of the entity to export.
   *
   * @command default-content:export
   * @option file Write out the exported content to a file (must end with .yml) instead of stdout.
   * @aliases dce
   */
  public function contentExport($entity_type_id, $entity_id, $options = ['file' => NULL]) {
    $export = $this->defaultContentExporter->exportContent($entity_type_id, $entity_id, $options['file']);

    if (!$options['file']) {
      $this->output()->write($export);
    }
  }

  /**
   * Exports an entity and all its referenced entities.
   *
   * @param string $entity_type_id
   *   The entity type to export.
   * @param int $entity_id
   *   The ID of the entity to export.
   *
   * @command default-content:export-references
   * @option folder Folder to export to, entities are grouped by entity type into directories.
   * @aliases dcer
   */
  public function contentExportReferences($entity_type_id, $entity_id = NULL, $options = ['folder' => NULL]) {
    $folder = $options['folder'];
    if (is_null($entity_id)) {
      $entities = \Drupal::entityQuery($entity_type_id)->accessCheck(FALSE)->execute();
    }
    else {
      $entities = [$entity_id];
    }
    // @todo Add paging.
    foreach ($entities as $entity_id) {
      $this->defaultContentExporter->exportContentWithReferences($entity_type_id, $entity_id, $folder);
    }
  }

  /**
   * Exports all the content defined in a module info file.
   *
   * @param string $module
   *   The name of the module.
   *
   * @command default-content:export-module
   * @aliases dcem
   */
  public function contentExportModule($module) {
    $module_folder = \Drupal::service('extension.list.module')
      ->get($module)
      ->getPath() . '/content';
    $this->defaultContentExporter->exportModuleContent($module, $module_folder);
  }

  /**
   * Exports all the content and references defined in a module info file.
   *
   * @param string $module
   *   The name of the module.
   *
   * @command default-content:export-module-with-references
   * @aliases dcemr
   */
  public function contentExportModuleWithReferences($module) {
    $module_folder = \Drupal::moduleHandler()
        ->getModule($module)
        ->getPath() . '/content';
    $this->defaultContentExporter->exportModuleContentWithReferences($module, $module_folder);
  }

}
