<?php

namespace Drupal\default_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;

/**
 * Finds, reads and writes default content files.
 */
class ContentFileStorage implements ContentFileStorageInterface {

  /**
   * The filesystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs the content file storage.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The filesystem service.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function scan($directory) {
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($directory, $flags);
    $iterator = new \RecursiveIteratorIterator($directory_iterator);

    $files = [];
    foreach ($iterator as $fileinfo) {
      /* @var \SplFileInfo $fileinfo */

      // Skip directories and non-json/yaml files.
      if ($fileinfo->isDir() || ($fileinfo->getExtension() != 'json' && $fileinfo->getExtension() != 'yml')) {
        continue;
      }

      // @todo Use a typed class?
      $file = new \stdClass();
      $file->name = $fileinfo->getFilename();
      $file->uri = $fileinfo->getPathname();
      $files[$file->uri] = $file;
    }

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function writeEntity(string $folder, string $encoded, ContentEntityInterface $entity, ?string $filename = NULL) {
    // Ensure that the folder per entity type exists.
    $entity_type_folder = "$folder/" . $entity->getEntityTypeId();
    $this->fileSystem->prepareDirectory($entity_type_folder, FileSystemInterface::CREATE_DIRECTORY);

    $filename = $filename ?: ($entity->uuid() . '.yml');
    file_put_contents($entity_type_folder . '/' . $filename, $encoded);

    // For files, copy the file into the same folder.
    if ($entity instanceof FileInterface) {
      $this->fileSystem->copy($entity->getFileUri(), $entity_type_folder . '/' . $entity->getFilename(), FileSystemInterface::EXISTS_REPLACE);
    }
  }

}
