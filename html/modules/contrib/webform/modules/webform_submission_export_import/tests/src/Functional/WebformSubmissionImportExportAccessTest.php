<?php

namespace Drupal\Tests\webform_submission_export_import\Functional;

/**
 * Webform submission export/import access test.
 *
 * @group webform_submission_import_export
 */
class WebformSubmissionImportExportAccessTest extends WebformSubmissionImportExportTestBase {

  /**
   * Test submission export/import access controls.
   */
  public function testAccessControls(): void {
    $assert_session = $this->assertSession();
    $upload_url = '/admin/structure/webform/manage/test_submission_export_import/results/upload';

    // Check that submission edit access is not enough to upload imports.
    $account = $this->drupalCreateUser(['edit any webform submission']);
    $this->drupalLogin($account);
    $this->drupalGet($upload_url);
    $assert_session->statusCodeEquals(403);

    // Check that the dedicated permission grants access to upload imports.
    $account = $this->drupalCreateUser([
      'edit any webform submission',
      'access webform submission export import',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet($upload_url);
    $assert_session->statusCodeEquals(200);
  }

}
