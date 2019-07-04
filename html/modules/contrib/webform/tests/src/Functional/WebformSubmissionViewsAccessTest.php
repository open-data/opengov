<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;

/**
 * Tests access rules in the context of webform submission views access.
 *
 * @group webform_browser
 */
class WebformSubmissionViewsAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'views',
    'webform',
    'webform_test_views',
  ];

  /**
   * Test webform submission entity access in a view query.
   */
  public function testEntityAccess() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Create any access user, own access user, and no (anonymous) access user.
    $any_user = $this->drupalCreateUser(['access webform overview']);
    $own_user = $this->drupalCreateUser(['access webform overview']);
    $without_access_user = $this->drupalCreateUser(['access webform overview']);

    // Grant any and own access to submissions.
    $webform->setAccessRules([
      'view_any' => ['users' => [$any_user->id()]],
      'view_own' => ['users' => [$own_user->id()]],
    ])->save();

    // Create an array of the accounts.
    $accounts = [
      'any_user' => $any_user,
      'own_user' => $own_user,
      'without_access' => $without_access_user,
    ];

    // Create test submissions.
    $this->createSubmissions($webform, $accounts);

    // Check user submission access.
    $this->checkUserSubmissionAccess($webform, $accounts);

    // Clear webform access rules.
    $webform->setAccessRules([])->save();

    // Check user submission access cache is cleared.
    $this->checkUserSubmissionAccess($webform, $accounts);
  }

  /**
   * Tests webform submission views enforce access per user's permissions.
   */
  public function testPermissionAccess() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Create any access user, own access user, and no (anonymous) access user.
    $own_webform_user = $this->drupalCreateUser([
      'access webform overview',
      'edit own webform',
    ]);
    $webform->setOwner($own_webform_user)->save();
    $any_submission_user = $this->drupalCreateUser([
      'access webform overview',
      'view any webform submission',
    ]);
    $own_submission_user = $this->drupalCreateUser([
      'access webform overview',
      'view own webform submission',
    ]);
    $without_access_user = $this->drupalCreateUser([
      'access webform overview',
    ]);

    // Create an array of the accounts.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = [
      'own_webform_user' => $own_webform_user,
      'any_submission_user' => $any_submission_user,
      'own_submission_user' => $own_submission_user,
      'without_access' => $without_access_user,
    ];

    // Create test submissions.
    $this->createSubmissions($webform, $accounts);

    // Check user submission access.
    $this->checkUserSubmissionAccess($webform, $accounts);

    // Clear any and own permissions for all accounts.
    foreach ($accounts as &$account) {
      $roles = $account->getRoles(TRUE);
      $rid = reset($roles);
      user_role_revoke_permissions($rid, [
        'view any webform submission',
        'view own webform submission',
        'edit own webform',
      ]);
    }

    // Check user submission access cache is cleared.
    $this->checkUserSubmissionAccess($webform, $accounts);
  }

  /**
   * Create test a submission for each account.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param array $accounts
   *   An associative array of test users.
   */
  protected function createSubmissions(WebformInterface $webform, array $accounts) {
    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate */
    $submission_generate = \Drupal::service('webform_submission.generate');

    // Create a test submission for each user account.
    foreach ($accounts as $account) {
      WebformSubmission::create([
        'webform_id' => $webform->id(),
        'uid' => $account->id(),
        'data' => $submission_generate->getData($webform),
      ])->save();
    }
  }

  /**
   * Check user submission access.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param array $accounts
   *   An associative array of test users.
   *
   * @see \Drupal\webform_access\Tests\WebformAccessSubmissionViewsTest::checkUserSubmissionAccess
   */
  protected function checkUserSubmissionAccess(WebformInterface $webform, array $accounts) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = \Drupal::entityTypeManager()
      ->getStorage('webform_submission');

    // Reset the static cache to make sure we are hitting actual fresh access
    // results.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    \Drupal::entityTypeManager()->getAccessControlHandler('webform_submission')->resetCache();

    foreach ($accounts as $account_type => $account) {
      // Login the current user.
      $this->drupalLogin($account);

      // Get the webform_test_views_access view and the sid for each
      // displayed record.  Submission access is controlled via the query.
      // @see webform_query_webform_submission_access_alter()
      $this->drupalGet('/admin/structure/webform/test/views_access');

      $views_sids = [];
      foreach ($this->getSession()->getPage()->findAll('css', '.view .view-content tbody .views-field-sid') as $node) {
        $views_sids[] = $node->getText();
      }
      sort($views_sids);

      $expected_sids = [];

      // Load all webform submissions and check access using the access method.
      // @see \Drupal\webform\WebformSubmissionAccessControlHandler::checkAccess
      $webform_submissions = $webform_submission_storage->loadByEntities($webform);

      foreach ($webform_submissions as $webform_submission) {
        if ($webform_submission->access('view', $account)) {
          $expected_sids[] = $webform_submission->id();
        }
      }

      sort($expected_sids);

      // Check that the views sids is equal to the expected sids.
      $this->assertSame($expected_sids, $views_sids, "User '" . $account_type . "' access has correct access through view on webform submission entity type.");
    }
  }

}
