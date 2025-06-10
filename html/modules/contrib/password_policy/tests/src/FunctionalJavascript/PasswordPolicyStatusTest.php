<?php

declare(strict_types=1);

namespace Drupal\Tests\password_policy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the password policy status message when password is added or updated.
 *
 * @group password_policy
 */
class PasswordPolicyStatusTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'password_policy_length',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer users',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests pass_policy status message when password added or updated.
   */
  public function testPasswordStatusVisibility(): void {
    $this->drupalGet('admin/config/security/password-policy/add');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create new password policy.
    $assert->fieldExists("Policy Name")->setValue("test");
    $submit_button = $assert->buttonExists("Save");
    $submit_button->press();

    // Choose password_policy for the role: Authenticated user.
    $assert->fieldExists("Authenticated user")->check();
    $submit_button->press();

    $assert->buttonExists("Configure Constraint Settings")->press();
    $assert->assertWaitOnAjaxRequest();

    // Assert modal appears.
    $modal = $assert->elementExists('css', '[aria-describedby="drupal-modal"]');

    // Set minimum password_length to 5 and save the configuration.
    $assert->fieldExists("Number of characters", $modal)->setValue("5");
    $assert->buttonExists("Save", $modal)->press();
    $this->getSession()->reload();

    // Create new user.
    $this->drupalGet("/admin/people/create");

    $assert->fieldExists("Email address")->setValue("test@test.com");
    $assert->fieldExists("Password")->setValue("123");
    $assert->fieldExists("Confirm password")->setValue("123");

    // Minimum password length is 5, so failed status message should appear.
    $assert->pageTextContains("Fail - Password length must be at least 5 characters");

    $assert->fieldExists("Password")->setValue("12345");
    $assert->fieldExists("Confirm password")->setValue("12345");

    // The password length is 5 now, so failed status message shouldn't appear.
    $assert->pageTextNotContains("Fail - Password length must be at least 5 characters");
  }

}
