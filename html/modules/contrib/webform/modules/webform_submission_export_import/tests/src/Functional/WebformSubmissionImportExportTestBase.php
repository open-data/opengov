<?php

namespace Drupal\Tests\webform_submission_export_import\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Webform submission export/import test.
 *
 * @group webform_submission_import_export
 */
abstract class WebformSubmissionImportExportTestBase extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'webform',
    'webform_submission_export_import',
    'webform_submission_export_import_test',
  ];

  /* ************************************************************************ */

  /**
   * Load a webform submission using a property value.
   *
   * @param string $property
   *   A submission property.
   * @param string|int $value
   *   A property value.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   */
  protected function loadSubmissionByProperty($property, $value) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    // Always reset the cache.
    $submission_storage->resetCache();

    $submissions = $submission_storage->loadByProperties([$property => $value]);
    return reset($submissions);
  }

  /**
   * Get temporary submission export/import CSV files.
   *
   * @return array
   *   An array of temporary import file URIs.
   */
  protected function getTemporaryImportFiles(): array {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $files = $file_system->scanDirectory('temporary://', '/^webform_submission_export_import_.*\.csv$/');
    $file_uris = array_keys($files);
    sort($file_uris);
    return $file_uris;
  }

  /**
   * Set remote CSV URL hosts for a test.
   */
  protected function setRemoteCsvUrlHosts(array|bool $hosts = ['web', 'localhost', '*.google.com']): void {
    $this->writeSettings([
      'settings' => [
        'webform_submission_export_import_csv_hosts' => (object) [
          'value' => $hosts,
          'required' => TRUE,
        ],
      ],
    ]);
  }

  /**
   * Set remote file URL hosts for a test.
   */
  protected function setRemoteFileUrlHosts(array|bool $hosts = ['web', 'localhost', '*.google.com']): void {
    $this->writeSettings([
      'settings' => [
        'webform_submission_export_import_file_hosts' => (object) [
          'value' => $hosts,
          'required' => TRUE,
        ],
      ],
    ]);
  }

}
