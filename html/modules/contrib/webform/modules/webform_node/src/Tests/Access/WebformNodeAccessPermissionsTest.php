<?php

namespace Drupal\webform_node\Tests\Access;

use Drupal\webform\Entity\Webform;
use Drupal\webform_node\Tests\WebformNodeTestBase;

/**
 * Tests for webform node access permissions.
 *
 * @group WebformNode
 */
class WebformNodeAccessPermissionsTest extends WebformNodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_node'];

  /**
   * Tests webform node access permissions.
   *
   * @see \Drupal\webform\Tests\Access\WebformAccessPermissionTest::testWebformSubmissionAccessPermissions
   */
  public function testAccessPermissions() {
    global $base_path;

    // Create webform node that references the contact webform.
    $webform = Webform::load('contact');
    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    // Own webform submission user.
    $submission_own_account = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    // Any webform submission user.
    $submission_any_account = $this->drupalCreateUser([
      'view any webform submission',
      'edit any webform submission',
      'delete any webform submission',
    ]);

    /**************************************************************************/
    // Own submission permissions (authenticated).
    /**************************************************************************/

    $this->drupalLogin($submission_own_account);

    $edit = ['subject' => '{subject}', 'message' => '{message}'];
    $sid_1 = $this->postNodeSubmission($node, $edit);

    // Check view own previous submission message.
    $this->drupalGet("node/{$nid}");
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}node/{$nid}/webform/submissions/{$sid_1}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_1}");
    $this->assertResponse(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_1}/edit");
    $this->assertResponse(200);

    // Check 'delete own submission' permission.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_1}/delete");
    $this->assertResponse(200);

    $sid_2 = $this->postNodeSubmission($node, $edit);

    // Check view own previous submissions message.
    $this->drupalGet("node/{$nid}");
    $this->assertRaw('You have already submitted this webform.');
    $this->assertRaw("<a href=\"{$base_path}node/{$nid}/webform/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("node/{$nid}/webform/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submissions/{$sid_1}");
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submissions/{$sid_2}");

    // Check submission user duplicate returns access denied.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_2}/duplicate");
    $this->assertResponse(403);

    // Enable submission user duplicate.
    $webform->setSetting('submission_user_duplicate', TRUE);
    $webform->save();

    // Check submission user duplicate returns access allows.
    $this->drupalGet("node/{$nid}/webform/submissions/{$sid_2}/duplicate");
    $this->assertResponse(200);

    // Check webform results access denied.
    $this->drupalGet("node/{$nid}/webform/results/submissions");
    $this->assertResponse(403);

    /**************************************************************************/
    // Any submission permissions.
    /**************************************************************************/

    // Login as any user.
    $this->drupalLogin($submission_any_account);

    // Check webform results access allowed.
    $this->drupalGet("node/{$nid}/webform/results/submissions");
    $this->assertResponse(200);
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submission/{$sid_1}");
    $this->assertLinkByHref("{$base_path}node/{$nid}/webform/submission/{$sid_2}");

    // Check webform submission access allowed.
    $this->drupalGet("node/{$nid}/webform/submission/{$sid_1}");
    $this->assertResponse(200);
  }

}
