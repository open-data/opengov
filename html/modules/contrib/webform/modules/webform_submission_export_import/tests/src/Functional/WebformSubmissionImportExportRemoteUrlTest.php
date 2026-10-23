<?php

namespace Drupal\Tests\webform_submission_export_import\Functional;

use Drupal\Core\Url;

/**
 * Webform submission export/import remote URL test.
 *
 * @group webform_submission_import_export
 */
class WebformSubmissionImportExportRemoteUrlTest extends WebformSubmissionImportExportTestBase {

  /**
   * Test remote URL support.
   */
  public function testRemoteUrlSupport(): void {
    $assert_session = $this->assertSession();
    $upload_url = '/admin/structure/webform/manage/test_submission_export_import/results/upload';

    $account = $this->drupalCreateUser([
      'edit any webform submission',
      'access webform submission export import',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet($upload_url);

    // Check that remote CSV URL controls and instructions are hidden for
    // non-administrators when remote CSV URL support is not configured.
    $assert_session->elementNotExists('css', '[name="import_type"]');
    $assert_session->fieldNotExists('Enter Submission CSV remote URL');
    $assert_session->pageTextNotContains('To enable remote CSV imports');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet($upload_url);

    // Check that remote URL configuration instructions are displayed to
    // administrators.
    $assert_session->responseContains('webform_submission_export_import_csv_hosts');
    $assert_session->responseContains('webform_submission_export_import_file_hosts');
    $assert_session->responseContains('docs.google.com');
    $assert_session->responseContains('remote managed-file URLs');

    /** @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $importer */
    $importer = \Drupal::service('webform_submission_export_import.importer');

    // Check that remote CSV and file URLs are rejected without configuration.
    $this->assertFalse($importer->isRemoteCsvUrlAllowed('http://example.com/example.csv'));
    $this->assertFalse($importer->isRemoteFileUrlAllowed('http://example.com/example.csv'));

    $this->setRemoteCsvUrlHosts();
    $this->drupalGet($upload_url);

    // Check that configured CSV URL hosts enable the control and only allow
    // exact and wildcard-matching hosts.
    $assert_session->fieldExists('Enter Submission CSV remote URL');
    $assert_session->pageTextNotContains('To enable remote CSV imports');
    $this->assertTrue($importer->isRemoteCsvUrlSupported());
    $this->assertFalse($importer->isRemoteFileUrlSupported());
    $this->assertTrue($importer->isRemoteCsvUrlAllowed('http://web/example.csv'));
    $this->assertTrue($importer->isRemoteCsvUrlAllowed('https://sheets.google.com/example.csv'));
    $this->assertFalse($importer->isRemoteCsvUrlAllowed('https://google.com/example.csv'));
    $this->assertFalse($importer->isRemoteCsvUrlAllowed('http://example.com/example.csv'));
    $this->assertFalse($importer->isRemoteFileUrlAllowed('http://web/example.csv'));

    $this->setRemoteFileUrlHosts();

    // Check that configured file URL hosts independently allow exact and
    // wildcard-matching managed-file URLs.
    $this->assertTrue($importer->isRemoteFileUrlSupported());
    $this->assertTrue($importer->isRemoteFileUrlAllowed('http://web/example.csv'));
    $this->assertTrue($importer->isRemoteFileUrlAllowed('https://sheets.google.com/example.csv'));
    $this->assertFalse($importer->isRemoteFileUrlAllowed('https://google.com/example.csv'));
    $this->assertFalse($importer->isRemoteFileUrlAllowed('http://example.com/example.csv'));

    $this->setRemoteCsvUrlHosts(FALSE);
    $this->drupalGet($upload_url);

    // Check that explicitly disabling CSV URL hosts hides its control and
    // administrator instructions.
    $assert_session->elementNotExists('css', '[name="import_type"]');
    $assert_session->fieldNotExists('Enter Submission CSV remote URL');
    $assert_session->pageTextNotContains('To enable remote CSV imports');

    $this->setRemoteCsvUrlHosts(['*']);
    $this->setRemoteFileUrlHosts(['*']);

    // Check that global wildcards allow any CSV and managed-file URL host.
    $this->assertTrue($importer->isRemoteCsvUrlAllowed('https://example.com/example.csv'));
    $this->assertTrue($importer->isRemoteFileUrlAllowed('http://files.example.com/example.gif'));

    $redirect_url = Url::fromRoute('webform_submission_export_import_test.redirect', [], ['absolute' => TRUE])->toString();
    $redirect_host = parse_url($redirect_url, PHP_URL_HOST);

    // Check that the redirect test URL has a host.
    $this->assertIsString($redirect_host);
    $this->setRemoteCsvUrlHosts([$redirect_host]);
    $this->setRemoteFileUrlHosts([$redirect_host]);

    // Check that redirects are rejected for CSV and managed-file URLs.
    $this->assertNull($importer->getRemoteCsvUrlContents($redirect_url));
    $this->assertNull($importer->getRemoteFileUrlContents($redirect_url));
  }

}
