<?php

namespace Drupal\default_content;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Finds, reads and writes default content files.
 */
interface ContentFileStorageInterface {

  /**
   * Returns a list of file objects.
   *
   * @param string $directory
   *   Absolute path to the directory to search.
   *
   * @return object[]
   *   List of stdClass objects with name and uri properties.
   */
  public function scan($directory);

  /**
   * Writes a normalized entity to the given folder.
   *
   * @param string $folder
   *   The target folder.
   * @param string $encoded
   *   The encoded entity (YAML).
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content being written.
   * @param string $filename
   *   (optional) The name of the file, defaults to UUID.yml. Must end with
   *   .yml.
   */
  public function writeEntity(string $folder, string $encoded, ContentEntityInterface $entity, ?string $filename = NULL);

}
