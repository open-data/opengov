<?php

namespace Drupal\Tests\password_policy_character_types\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests password character types operations.
 *
 * @group password_policy_character_types
 */
class PasswordCharacterTypesOperations extends BrowserTestBase {

  /**
   * Modules to enable at the start of the test.
   *
   * @var array
   */
  public static $modules = [
    'password_policy_character_types',
    'password_policy',
  ];

  /**
   * Administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the management of the "character_types" constraint.
   */
  public function testPasswordCharacterTypesManagement() {
    // Create a policy and add a "character_types" constraint.
    $this->drupalPostForm('admin/config/security/password-policy/add', ['label' => 'Test policy', 'id' => 'test_policy'], 'Next');
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/character_types');
    $this->assertSession()->pageTextContains('Minimum number of character types');

    $this->drupalPostForm(NULL, ['character_types' => 2], 'Save');
    $this->assertSession()->pageTextContains('Minimum password character types: 2');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/character_types');
    $this->drupalPostForm(NULL, ['character_types' => 3], 'Save');
    $this->assertSession()->pageTextContains('Minimum password character types: 3');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/character_types');
    $this->drupalPostForm(NULL, ['character_types' => 4], 'Save');
    $this->assertSession()->pageTextContains('Minimum password character types: 4');
  }

}
