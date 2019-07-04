<?php

namespace Drupal\webform_access\Tests;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform tokens access.
 *
 * @group WebformAccess
 */
class WebformAccessTokensTest extends WebformAccessTestBase {

  /**
   * Tests webform access tokens.
   */
  public function testWebformAccessTokens() {
    // Add both users to employee group.
    foreach ($this->users as $account) {
      $this->groups['employee']->addUserId($account->id());
    }
    $this->groups['employee']->save();
    $this->users['other'] = $this->drupalCreateUser([], 'other_user');
    $this->groups['manager']->setUserIds([$this->users['other']->id()]);
    $this->groups['manager']->save();

    // Create a submission.
    $edit = [
      'name' => 'name',
      'email' => 'name@example.com',
      'subject' => 'subject',
      'message' => 'message',
    ];
    $sid = $this->postNodeSubmission($this->nodes['contact_01'], $edit);
    $webform_submission = WebformSubmission::load($sid);

    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    $token_data['webform_access'] = $webform_submission;

    // Check [webform_access:type:employee] token.
    $result = $token_manager->replace('[webform_access:type:employee]', $webform_submission, $token_data);
    $this->assertEqual('customer_user@example.com,employee_user@example.com,manager_user@example.com', $result);

    // Check [webform_access:type:manager] token.
    $result = $token_manager->replace('[webform_access:type:manager]', $webform_submission, $token_data);
    $this->assertEqual('other_user@example.com', $result);

    // Check [webform_access:type:all] token.
    $result = $token_manager->replace('[webform_access:type]', $webform_submission, $token_data);
    $this->assertEqual('customer_user@example.com,employee_user@example.com,manager_user@example.com,other_user@example.com', $result);
  }

}
