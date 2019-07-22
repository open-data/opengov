<?php

namespace Drupal\webform_entity_print\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_print\Plugin\ExportTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base deriver for webform entity print plugins.
 */
abstract class WebformEntityPrintWebformDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity print export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * Constructs new WebformEntityPrintWebformDeriverBase.
   *
   * @param \Drupal\entity_print\Plugin\ExportTypeManagerInterface $export_type_manager
   *   The entity print export type manager.
   */
  public function __construct(ExportTypeManagerInterface $export_type_manager) {
    $this->exportTypeManager = $export_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.entity_print.export_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    return [];
  }

  /**
   * Get export type definitions.
   *
   * @return array
   *   An array of export type definitions.
   */
  protected function getDefinitions() {
    $definitions = $this->exportTypeManager->getDefinitions();

    // Remove unsupported export types.
    // @see Issue #2733781: Add Export to Word Support.
    unset($definitions['word_docx'], $definitions['epub']);

    return $definitions;
  }

}
