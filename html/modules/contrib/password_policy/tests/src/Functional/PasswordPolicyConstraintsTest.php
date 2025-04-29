<?php

namespace Drupal\Tests\password_policy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the password policy status is shown alongside the password.
 *
 * @group password_policy
 */
class PasswordPolicyConstraintsTest extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'password_policy',
    'password_policy_length',
    'password_policy_username',
    'node',
  ];

  /**
   * Tests the visibility of the password policy status with no password field.
   */
  public function testHiddenPasswordField() {
    // Create user with permission to create policy.
    $user = $this->drupalCreateUser([
      'administer site configuration',
    ]);

    $this->drupalLogin($user);

    // Create new password policy.
    $this->drupalGet('admin/config/security/password-policy/add');
    $edit = [
      'id' => 'test',
      'label' => 'test',
    ];

    // Save policy info.
    $this->submitForm($edit, 'Save');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_username');

    // The password_username constraint doesn't have any form configuration.
    $this->submitForm([], 'Save');

    // Fill out length constraint for test policy.
    $edit = [
      'character_length' => '1',
      'character_operation' => 'minimum',
    ];

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test/password_length');
    $this->submitForm($edit, 'Save');

  }

}
