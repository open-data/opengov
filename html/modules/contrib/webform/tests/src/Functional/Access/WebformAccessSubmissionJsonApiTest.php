<?php

namespace Drupal\Tests\webform\Functional\Access;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\basic_auth\Traits\BasicAuthTestTrait;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission JSON:API access.
 *
 * @group webform
 */
class WebformAccessSubmissionJsonApiTest extends WebformBrowserTestBase {

  use BasicAuthTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'jsonapi', 'basic_auth'];

  /**
   * Tests JSON:API webform submission collection access varies per user.
   */
  public function testSubmissionCollectionAccessCacheContexts(): void {
    $assert_session = $this->assertSession();
    $webform = Webform::load('contact');
    $access_rules = $webform->getAccessRules();
    $access_rules['view_own']['roles'] = ['authenticated'];
    $webform->setAccessRules($access_rules)->save();

    $first_account_password = $this->randomMachineName();
    $second_account_password = $this->randomMachineName();
    $first_account = $this->drupalCreateUser([], NULL, FALSE, ['pass' => $first_account_password]);
    $second_account = $this->drupalCreateUser([], NULL, FALSE, ['pass' => $second_account_password]);

    $first_submission_id = $this->createSubmission($webform, $first_account->id());
    $second_submission_id = $this->createSubmission($webform, $second_account->id());

    // Check that the first account can only see its own submission.
    $first_submission_ids = $this->getJsonApiSubmissionIds($first_account->getAccountName(), $first_account_password);
    $assert_session->statusCodeEquals(200);
    $this->assertSame([$first_submission_id], $first_submission_ids);

    // Check that the second account can only see its own submission.
    $second_submission_ids = $this->getJsonApiSubmissionIds($second_account->getAccountName(), $second_account_password);
    $assert_session->statusCodeEquals(200);
    $this->assertSame([$second_submission_id], $second_submission_ids);
  }

  /**
   * Creates a contact webform submission owned by a user.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform.
   * @param int $user_id
   *   The submission owner ID.
   *
   * @return int
   *   The saved webform submission ID.
   */
  protected function createSubmission(Webform $webform, int $user_id): int {
    $webform_submission = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'uid' => $user_id,
      'data' => [
        'name' => $this->randomString(),
        'email' => $this->randomMachineName() . '@example.com',
        'subject' => $this->randomString(),
        'message' => $this->randomString() . ' ' . $this->randomString(),
      ],
    ]);
    $webform_submission->save();

    return (int) $webform_submission->id();
  }

  /**
   * Gets JSON:API webform submission IDs for a basic auth account.
   *
   * @param string $account_name
   *   The account name.
   * @param string $password
   *   The account password.
   *
   * @return array
   *   The submission IDs returned by the JSON:API collection.
   */
  protected function getJsonApiSubmissionIds(string $account_name, string $password): array {
    $content = $this->basicAuthGet('/jsonapi/webform_submission/contact', $account_name, $password);
    $document = Json::decode($content);

    return array_map(static function (array $resource) {
      return $resource['attributes']['drupal_internal__sid'];
    }, $document['data']);
  }

}
