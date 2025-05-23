<?php

namespace Drupal\Tests\password_policy_length\Functional;

use Drupal\Component\Utility\Random;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests password length operations.
 *
 * @group password_policy_length
 */
class PasswordLengthOperations extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['password_policy_length', 'password_policy'];

  /**
   * Administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->adminUser);

    // Create a policy.
    $this->drupalGet('admin/config/security/password-policy/add');
    $this->submitForm(['label' => 'Test policy', 'id' => 'test_policy'], 'Save');
  }

  /**
   * Test the management of the "length" constraint.
   */
  public function testPasswordLengthManagement() {
    // Add minimum and maximum "length" constraints.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->assertSession()->pageTextContains('Number of characters');
    $this->assertSession()->pageTextContains('Operation');

    $this->submitForm([
      'character_operation' => 'minimum',
      'character_length' => 5,
    ], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');

    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_operation' => 'maximum',
      'character_length' => 10,
    ], 'Save');
    $this->drupalGet('admin/config/security/password-policy/test_policy');

    // Add minimum character_length constraint again, and it should fail.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_length' => 4,
      'character_operation' => 'minimum',
    ], 'Save');

    $this->assertSession()->statusMessageContains('The selected operation (minimum) already exists.');

    // Add maximum character_length constraint again, and it should fail.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_length' => 8,
      'character_operation' => 'maximum',
    ], 'Save');

    $this->assertSession()->statusMessageContains('The selected operation (maximum) already exists.');

    // Add maximum character_length constraint lower than minimum
    // character_length, and it should fail.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_length' => 4,
      'character_operation' => 'maximum',
    ], 'Save');

    $this->assertSession()->statusMessageContains('The selected length (4) is lower than the minimum length defined (5).');

    $entity = $this->container->get('entity_type.manager')->getStorage('password_policy')->load('test_policy');
    $constraints = $entity->get('policy_constraints');
    // Remove the minimum character_length constraint from policy_constraints.
    // so that we can check if we get error when we try to add minimum
    // character_length constraint grater than the maximum character_length
    // constraint.
    array_shift($constraints);
    $entity->set('policy_constraints', $constraints);
    $entity->save();

    // Now add minimum character_length constraint with value greater than
    // maximum character_length, and it should fail.
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_length' => 11,
      'character_operation' => 'minimum',
    ], 'Save');

    $this->assertSession()->statusMessageContains('The selected length (11) is higher than the maximum length defined (10).');
  }

  /**
   * Test all validations of the "length" constraint.
   *
   * @param string $character_operation
   *   The character_operation i.e minimum or maximum.
   * @param string|int $character_length
   *   Given character length.
   * @param string|null $status_message
   *   The expected status message.
   * @param string|null $page_text
   *   The expected page text.
   *
   * @dataProvider passwordValidationDataProvider
   */
  public function testPasswordLengthValidations(string $character_operation, string|int $character_length, string $status_message = NULL, string $page_text = NULL) {
    $this->drupalGet('admin/config/system/password_policy/constraint/add/test_policy/password_length');
    $this->submitForm([
      'character_operation' => $character_operation,
      'character_length' => $character_length,
    ], 'Save');
    if ($status_message) {
      $this->assertSession()->statusMessageContains($status_message);
    }
    if ($page_text) {
      // Visit back to password_policy page.
      $this->drupalGet('admin/config/security/password-policy/test_policy');
      $this->assertSession()->pageTextContains($page_text);
    }
  }

  /**
   * The dataProvider for the testPasswordLengthValidations().
   */
  public static function passwordValidationDataProvider(): array {
    $random = new Random();
    return [
      [
        'character_operation' => 'minimum',
        'character_length' => '',
        'status_message' => 'The character length must be a positive number.',
      ],
      [
        'character_operation' => 'minimum',
        'character_length' => '-1',
        'status_message' => 'The character length must be a positive number.',
      ],
      [
        'character_operation' => 'minimum',
        'character_length' => $random->machineName(),
        'status_message' => 'The character length must be a positive number.',
      ],
      [
        'character_operation' => 'minimum',
        'character_length' => 1,
        'status_message' => NULL,
        'page_text' => 'Password character length of at least 1',
      ],
      [
        'character_operation' => 'maximum',
        'character_length' => 6,
        'status_message' => NULL,
        'page_text' => 'Password character length of at most 6',
      ],
    ];
  }

}
