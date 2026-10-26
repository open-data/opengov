<?php

namespace Drupal\webform_submission_export_import_test\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides test responses for Webform submission export/import.
 */
class WebformSubmissionExportImportTestController {

  /**
   * Returns a redirect response.
   */
  public function redirect(): RedirectResponse {
    return new RedirectResponse('/webform-submission-export-import-test/target');
  }

}
