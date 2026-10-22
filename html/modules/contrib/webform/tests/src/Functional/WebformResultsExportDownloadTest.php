<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results export download.
 *
 * @group webform
 */
class WebformResultsExportDownloadTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'locale', 'webform', 'token', 'webform_attachment'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_exporter_archive'];

  /**
   * Tests export file access, directory isolation, and filename validation.
   */
  public function testDownloadFileAccess(): void {
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_exporter_archive');

    $root_directory = \Drupal::service('file_system')->getTempDirectory() . '/webform-export-' . $this->randomMachineName();

    // Check that the configured temporary root can be created.
    $this->assertTrue(mkdir($root_directory));
    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $original_directory = $config->get('export.temp_directory');
    $config->set('export.temp_directory', $root_directory)->save();

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $exporter = $submission_exporter->setExporter();
    $export_directory = $submission_exporter->getFileTempDirectory();

    // Check that both exporters use the created Webform subdirectory.
    $this->assertSame($root_directory . '/webform', $export_directory);
    $this->assertSame($export_directory, $exporter->getFileTempDirectory());
    $this->assertDirectoryExists($export_directory);

    // Check that files can be written inside and outside the export directory.
    $filename = $webform->id() . '.node.1.csv';
    $file_path = $export_directory . '/' . $filename;
    $file_contents = 'Unrelated file contents.';
    $this->assertNotFalse(file_put_contents($file_path, $file_contents));

    $export_filename = $webform->id() . '.csv';
    $export_file_path = $export_directory . '/' . $export_filename;
    $export_file_contents = 'Webform export file contents.';
    $this->assertNotFalse(file_put_contents($export_file_path, $export_file_contents));

    $outside_file_path = $root_directory . '/' . $export_filename;
    $outside_file_contents = 'File outside the Webform export directory.';
    $this->assertNotFalse(file_put_contents($outside_file_path, $outside_file_contents));

    $archive_filename = $webform->id() . '.tar.gz';
    $archive_file_path = $export_directory . '/' . $archive_filename;
    $archive_file_contents = 'Webform archive file contents.';
    $this->assertNotFalse(file_put_contents($archive_file_path, $archive_file_contents));

    $missing_filename = $webform->id() . '.json';

    try {
      /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
      $access_rules_manager = \Drupal::service('webform.access_rules_manager');
      $access_rules = [
        'view_any' => [
          'roles' => [],
          'users' => [],
          'permissions' => ['access content'],
        ],
      ] + $access_rules_manager->getDefaultAccessRules();
      $webform->setAccessRules($access_rules)->save();

      $account = $this->drupalCreateUser(['access content']);
      $this->drupalLogin($account);
      $this->drupalGet("/admin/structure/webform/manage/{$webform->id()}/results/download/file/$filename");

      // Check that a file for another source entity is denied and retained.
      $assert_session->statusCodeEquals(404);
      $assert_session->responseNotContains($file_contents);
      $this->assertFileExists($file_path);

      $this->drupalGet("/admin/structure/webform/manage/{$webform->id()}/results/download/file/$missing_filename");

      // Check that a missing valid export keeps the existing guidance.
      $assert_session->statusCodeEquals(200);
      $assert_session->responseContains('No export file ready for download.');

      $this->drupalGet("/admin/structure/webform/manage/{$webform->id()}/results/download/file/$export_filename");

      // Check that the matching export can be downloaded.
      $assert_session->statusCodeEquals(200);
      $assert_session->responseContains($export_file_contents);

      $this->drupalGet("/admin/structure/webform/manage/{$webform->id()}/results/download/file/$archive_filename");

      // Check that a matching archive can be downloaded.
      $assert_session->statusCodeEquals(200);
      $assert_session->responseContains($archive_file_contents);

      $download_url = "/admin/structure/webform/manage/{$webform->id()}/results/download";

      // Check that the export page rejects both path separator styles.
      foreach (['../' . $export_filename, '..\\' . $export_filename] as $invalid_filename) {
        $this->drupalGet($download_url, ['query' => ['filename' => $invalid_filename]]);
        $assert_session->statusCodeEquals(404);
      }
      $this->drupalGet($download_url . '/file/..%2F' . $export_filename);

      // Check that a route traversal cannot read or change files on either side.
      $this->assertContains($this->getSession()->getStatusCode(), [403, 404]);
      $assert_session->responseNotContains($outside_file_contents);
      $this->assertSame($outside_file_contents, file_get_contents($outside_file_path));
      $this->assertSame($export_file_contents, file_get_contents($export_file_path));
    }
    finally {
      $config->set('export.temp_directory', $original_directory)->save();
      @unlink($file_path);
      @unlink($export_file_path);
      @unlink($archive_file_path);
      @unlink($outside_file_path);
      @rmdir($export_directory);
      @rmdir($root_directory);
    }
  }

  /**
   * Tests download files.
   */
  public function testDownloadFiles(): void {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_exporter_archive');

    /** @var \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('webform_submission.exporter');
    $submission_exporter->setWebform($webform);
    $submission_exporter->setExporter();

    $sids = [];
    $sids[] = $this->postSubmissionTest($webform);
    $sids[] = $this->postSubmissionTest($webform);
    $sids[] = $this->postSubmissionTest($webform);

    $tests = [
      [
        'archive_type' => 'tar',
        'files' => TRUE,
        'attachments' => FALSE,
      ],
      [
        'archive_type' => 'zip',
        'files' => TRUE,
        'attachments' => FALSE,
      ],
      [
        'archive_type' => 'tar',
        'files' => FALSE,
        'attachments' => TRUE,
      ],
      [
        'archive_type' => 'zip',
        'files' => FALSE,
        'attachments' => TRUE,
      ],
    ];
    foreach ($tests as $test) {
      // Set exporter archive type.
      $submission_exporter->setExporter(['archive_type' => $test['archive_type']]);

      /* Download CSV */

      // Download archive with CSV (delimited).
      $this->drupalGet('/admin/structure/webform/manage/test_exporter_archive/results/download');
      $edit = [
        'exporter' => 'delimited',
        'archive_type' => $test['archive_type'],
        'files' => $test['files'],
        'attachments' => $test['attachments'],
      ];
      $this->submitForm($edit, 'Download');

      // Load the archive and get a list of files.
      $files = $this->getArchiveContents($submission_exporter->getArchiveFilePath());

      // Check that CSV file exists.
      $this->debug($files);
      $this->assertArrayHasKey('test_exporter_archive/test_exporter_archive.csv', $files);

      // Check submission file directories.
      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = WebformSubmission::loadMultiple($sids);
      foreach ($submissions as $submission) {
        $serial = $submission->serial();

        if ($test['files']) {
          $fid = $submission->getElementData('managed_file');
          $filename = File::load($fid)->getFilename();
          $this->assertArrayHasKey("submission-$serial/$filename", $files);
        }
      }

      /* Download YAML */

      // Download archive with YAML documents.
      $this->drupalGet('/admin/structure/webform/manage/test_exporter_archive/results/download');
      $edit = [
        'exporter' => 'yaml',
        'archive_type' => $test['archive_type'],
        'files' => $test['files'],
        'attachments' => $test['attachments'],
      ];
      $this->submitForm($edit, 'Download');

      // Load the archive and get a list of files.
      $files = $this->getArchiveContents($submission_exporter->getArchiveFilePath());

      // Check that CSV file does not exists.
      $this->assertArrayNotHasKey('test_exporter_archive/test_exporter_archive.csv', $files);

      // Check submission file directories.
      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = WebformSubmission::loadMultiple($sids);
      foreach ($submissions as $submission) {
        $serial = $submission->serial();

        $this->assertArrayHasKey("submission-$serial.yml", $files);

        if ($test['files']) {
          $fid = $submission->getElementData('managed_file');
          $filename = File::load($fid)->getFilename();
          $this->assertArrayHasKey("submission-$serial/$filename", $files);
        }
      }
    }
  }

  /**
   * Get archive contents.
   *
   * @param string $filepath
   *   Archive file path.
   *
   * @return array
   *   Array of archive contents.
   */
  protected function getArchiveContents($filepath) {
    if (str_contains($filepath, '.zip')) {
      $archive = new \ZipArchive();
      $archive->open($filepath);
      $files = [];
      for ($i = 0; $i < $archive->numFiles; $i++) {
        $files[] = $archive->getNameIndex($i);
      }
    }
    else {
      $archive = new \Archive_Tar($filepath, 'gz');
      $files = [];
      foreach ($archive->listContent() as $file_data) {
        $files[] = $file_data['filename'];
      }
    }
    return array_combine($files, $files);
  }

}
