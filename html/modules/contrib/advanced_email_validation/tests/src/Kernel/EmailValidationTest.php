<?php

namespace Drupal\Tests\advanced_email_validation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests module configuration.
 *
 * @group advanced_email_validation
 */
#[RunTestsInSeparateProcesses]
class EmailValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'advanced_email_validation',
  ];

  /**
   * The advanced email validation service.
   *
   * @var \Drupal\advanced_email_validation\AdvancedEmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->installConfig('advanced_email_validation');
    $this->installEntitySchema('user');

    $this->emailValidator = \Drupal::service('advanced_email_validation.validator');
  }

  /**
   * Test basic validation.
   */
  public function testBasicValidation(): void {
    $accountName = $this->randomMachineName();
    $validAccount = User::create([
      'name' => $accountName,
      'mail' => '',
    ]);
    $violations = $validAccount->validate();
    $this->assertEquals(1, $violations->count(), 'Only the "required field" constraint should fail when the email address is empty, not the email validation constraint.');
    $this->assertEquals('Email field is required.', $violations[0]->getMessage()->render());

    $accountName = $this->randomMachineName();
    $validAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@example.com',
    ]);
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Basic validation should pass with a valid email address.');

    $invalidEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $this->randomString(),
    ]);
    $violations = $invalidEmailAccount->validate();
    // Core will also fail validation on this one - hence 2 violations.
    $this->assertEquals(2, $violations->count(), 'Basic validation should fail with an invalid email address.');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage());

    $newError = $this->randomMachineName();
    $config = $this->config('advanced_email_validation.settings');
    $config->set('error_messages.basic', $newError)->save();
    $violations = $invalidEmailAccount->validate();
    $this->assertEquals($newError, $violations[0]->getMessage());
  }

  /**
   * Test account validation on creation and update.
   *
   * Tests the account validation when disabling both "created" and "updated"
   * in "validate_account_on".
   */
  public function testValidateAccountOnDisabled(): void {
    $config = $this->config('advanced_email_validation.settings');
    $validAccountName = $this->randomMachineName();

    // Set both "created" and "updated" to FALSE, the validation should never
    // be executed for both creating and updating the user:
    $config->set('validate_account_on', [
      'created' => FALSE,
      'updated' => FALSE,
    ])->save();

    // Create a valid account and validate it:
    $validAccount = User::create([
      'name' => $validAccountName,
      'mail' => $validAccountName . '@example.com',
    ]);
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is created.');
    // Save the account, so we can update it later:
    $validAccount->save();

    // Update the valid account and validate it:
    $validAccount->setEmail($this->randomMachineName() . '@example.com');
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is updated.');

    // Now create an invalid account. The validation should still pass, as both
    // validation methods are disabled:
    $invalidAccountName = $this->randomMachineName();
    $invalidAccount = User::create([
      'name' => $invalidAccountName,
      'mail' => $this->randomString(),
    ]);
    $violations = $invalidAccount->validate();
    // Core will fail validation on this one - hence 1 violation.
    $this->assertEquals(1, $violations->count(), 'On create with invalid email and AEV disabled, only the core email constraint should fail (1 violation).');
    // Save the invalid account, so we can update it later:
    $invalidAccount->save();

    // Update the invalid account email and validate it:
    $invalidAccount->setEmail($this->randomString());
    $violations = $invalidAccount->validate();
    // Core will fail validation on this one - hence 1 violation.
    $this->assertEquals(1, $violations->count(), 'On update with invalid email and AEV disabled, only the core email constraint should fail (1 violation).');
  }

  /**
   * Test account validation on creation and update.
   *
   * Tests the account validation when enabling "created" only in
   * "validate_account_on".
   */
  public function testValidateAccountOnCreated(): void {
    $config = $this->config('advanced_email_validation.settings');
    $validAccountName = $this->randomMachineName();

    // Set "created" to TRUE, the validation should never be executed when
    // updating the user:
    $config->set('validate_account_on', [
      'created' => TRUE,
      'updated' => FALSE,
    ])->save();

    // Create a valid account and validate it:
    $validAccount = User::create([
      'name' => $validAccountName,
      'mail' => $validAccountName . '@example.com',
    ]);
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is created.');
    // Save the account, so we can update it later:
    $validAccount->save();

    // Update the valid account and validate it:
    $validAccount->setEmail($this->randomMachineName() . '@example.com');
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is updated.');

    // Now create an invalid account. The validation should fail on creation,
    // but be fine on update:
    $invalidAccountName = $this->randomMachineName();
    $invalidAccount = User::create([
      'name' => $invalidAccountName,
      'mail' => $this->randomString(),
    ]);
    $violations = $invalidAccount->validate();
    $this->assertEquals(2, $violations->count(), 'On create with invalid email and AEV enabled for creation, both AEV and core should fail (2 violations).');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage());

    // Save the invalid account, so we can update it later:
    $invalidAccount->save();

    // Update the invalid account email and validate it:
    $invalidAccount->setEmail($this->randomString());
    $violations = $invalidAccount->validate();
    $this->assertEquals(1, $violations->count(), 'On update with invalid email and AEV disabled for updates, only the core email constraint should fail (1 violation).');
  }

  /**
   * Test account validation on creation and update.
   *
   * Tests the account validation when enabling "updated" only in
   * "validate_account_on".
   */
  public function testValidateAccountOnUpdate(): void {
    $config = $this->config('advanced_email_validation.settings');
    $validAccountName = $this->randomMachineName();

    // Set "updated" to TRUE, the validation should never be executed when
    // creating the user:
    $config->set('validate_account_on', [
      'created' => FALSE,
      'updated' => TRUE,
    ])->save();

    // Create a valid account and validate it:
    $validAccount = User::create([
      'name' => $validAccountName,
      'mail' => $validAccountName . '@example.com',
    ]);
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is created.');
    // Save the account, so we can update it later:
    $validAccount->save();

    // Update the valid account and validate it:
    $validAccount->setEmail($this->randomMachineName() . '@example.com');
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is updated.');

    // Now create an invalid account. The validation should fail on updating,
    // but be fine on creation:
    $invalidAccountName = $this->randomMachineName();
    $invalidAccount = User::create([
      'name' => $invalidAccountName,
      'mail' => $this->randomString(),
    ]);
    $violations = $invalidAccount->validate();
    $this->assertEquals(1, $violations->count(), 'On create with invalid email and AEV disabled for creation, only the core email constraint should fail (1 violation).');

    // Save the invalid account, so we can update it later:
    $invalidAccount->save();

    // Update the invalid account email and validate it, validation should fail:
    $invalidAccount->setEmail($this->randomString());
    $violations = $invalidAccount->validate();
    $this->assertEquals(2, $violations->count(), 'On update with invalid email and AEV enabled for updates, both AEV and core should fail (2 violations).');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage());
  }

  /**
   * Test account validation on creation and update.
   *
   * Tests the account validation when enabling both "created" and "updated" in
   * "validate_account_on".
   */
  public function testValidateAccountOnCreateAndUpdate(): void {
    $config = $this->config('advanced_email_validation.settings');
    $validAccountName = $this->randomMachineName();

    // Set both "created" and "updated" to TRUE, the validation should always
    // execute.
    $config->set('validate_account_on', [
      'created' => TRUE,
      'updated' => TRUE,
    ])->save();

    // Create a valid account and validate it:
    $validAccount = User::create([
      'name' => $validAccountName,
      'mail' => $validAccountName . '@example.com',
    ]);
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is created.');
    // Save the account, so we can update it later:
    $validAccount->save();

    // Update the valid account and validate it:
    $validAccount->setEmail($this->randomMachineName() . '@example.com');
    $violations = $validAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass when the account is updated.');

    // Now create an invalid account. The validation should always fail:
    $invalidAccountName = $this->randomMachineName();
    $invalidAccount = User::create([
      'name' => $invalidAccountName,
      'mail' => $this->randomString(),
    ]);
    $violations = $invalidAccount->validate();
    $this->assertEquals(2, $violations->count(), 'On create with invalid email and AEV enabled for creation, both AEV and core should fail (2 violations).');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage());

    // Save the invalid account, so we can update it later:
    $invalidAccount->save();

    // Update the invalid account email and validate it. Validation should fail:
    $invalidAccount->setEmail($this->randomString());
    $violations = $invalidAccount->validate();
    $this->assertEquals(2, $violations->count(), 'On update with invalid email and AEV enabled for updates, both AEV and core should fail (2 violations).');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage());
  }

  /**
   * Test MX Record checking which is turned on by default.
   */
  public function testMxRecordValidation(): void {
    // Test with a user that should pass.
    $accountName = $this->randomMachineName();
    $validMxAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@drupal.org',
    ]);
    $violations = $validMxAccount->validate();
    $this->assertEquals(0, $violations->count(), 'MX record validation should pass with an email using a valid domain.');

    // Syntactically correct domain that doesn't exist.
    $invalidMxAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@invalidinvalidinvalid.com',
    ]);
    $violations = $invalidMxAccount->validate();
    $this->assertEquals(1, $violations->count(), 'MX record validation should fail with an email using an invalid domain.');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage());

    // Change the error message.
    $newError = $this->randomMachineName();
    $config = $this->config('advanced_email_validation.settings');
    $config->set('error_messages.mx_lookup', $newError)->save();
    $violations = $invalidMxAccount->validate();
    $this->assertEquals($newError, $violations[0]->getMessage());

    // Turn the test off.
    $config->set('rules.mx_lookup', 0)->save();
    $violations = $invalidMxAccount->validate();
    $this->assertEquals(0, $violations->count(), 'MX record validation should be skipped when configured off.');
  }

  /**
   * Test an empty configured error message falls back to the default.
   *
   * When a rule is triggered but its configured error message is an empty
   * string, the new-entity validator (AEVNewEmail) must produce exactly one
   * violation carrying the constraint default, not two violations and not an
   * empty-message violation.
   */
  public function testEmptyErrorMessageFallsBackToDefaultOnCreate(): void {
    // Configure the MX lookup error message to an empty string. MX lookup is
    // enabled by default, and an invalid domain triggers it.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('error_messages.mx_lookup', '')->save();

    // The address is syntactically valid, so core's email constraint passes;
    // only the AEV constraint should fail on the MX lookup.
    $accountName = $this->randomMachineName();
    $invalidMxAccount = User::create([
      'name' => $accountName,
      'mail' => $this->randomMachineName() . '@invalidinvalidinvalid.com',
    ]);
    $violations = $invalidMxAccount->validate();
    $this->assertEquals(1, $violations->count(), 'An empty configured error message must yield exactly one violation, not two.');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage(), 'The empty configured message must fall back to the constraint default.');
  }

  /**
   * Test free email provider checking which is turned off by default.
   */
  public function testFreeProviderValidation(): void {
    // Test is skipped.
    $accountName = $this->randomMachineName();
    $freeEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@gmail.com',
    ]);
    $violations = $freeEmailAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Free provider validation should be skipped when configured off.');

    // Turn the test on.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('rules.free', 1)->save();
    $violations = $freeEmailAccount->validate();
    $this->assertEquals(1, $violations->count(), 'Free provider validation should fail with an email using a free email provider.');
    $this->assertEquals('Free public email providers are not allowed', $violations[0]->getMessage());

    // Change the error message.
    $newError = $this->randomMachineName();
    $config->set('error_messages.free', $newError)->save();
    $violations = $freeEmailAccount->validate();
    $this->assertEquals($newError, $violations[0]->getMessage());

    // Test with a user that should pass.
    $drupalEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@drupal.org',
    ]);
    $violations = $drupalEmailAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Free provider validation should pass with an email provided by a private provider.');
  }

  /**
   * Test free email provider list customization.
   *
   * MUST be a separate test because the list is statically cached by the 3rd
   * party library.
   */
  public function testFreeProviderListCustomization(): void {
    // User that should pass defaults.
    $accountName = $this->randomMachineName();
    $drupalEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@drupal.org',
    ]);

    // User that should fail free email validation when using fetched lists.
    $freeEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@gmail.com',
    ]);

    // Turn the test on and customize the list.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('rules.free', 1)
      ->set('domain_lists.free', ['drupal.org'])
      ->save();

    // Run validation.
    $violations = $drupalEmailAccount->validate();
    $this->assertEquals(1, $violations->count(), 'Custom free provider validation should fail with an email address on the custom free provider list.');

    // Turn on local-only validation.
    $config->set('local_list_only.free', TRUE)->save();
    $violations = $freeEmailAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Free provider validation should pass with an email using a free email provider that is not in the custom list.');
  }

  /**
   * Test disposable email provider checking which is turned off by default.
   */
  public function testDisposableProviderValidation(): void {
    // Test is skipped.
    $accountName = $this->randomMachineName();
    $disposableEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@mailinator.com',
    ]);
    $violations = $disposableEmailAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Disposable provider validation should be skipped when configured off.');

    // Turn the test on.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('rules.disposable', 1)->save();
    $violations = $disposableEmailAccount->validate();
    $this->assertEquals(1, $violations->count(), 'Disposable provider validation should fail with a disposable email address.');
    $this->assertEquals('Disposable emails are not allowed', $violations[0]->getMessage());

    // Change the error message.
    $newError = $this->randomMachineName();
    $config->set('error_messages.disposable', $newError)->save();
    $violations = $disposableEmailAccount->validate();
    $this->assertEquals($newError, $violations[0]->getMessage());

    // Test with a user that should pass.
    $drupalEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@drupal.org',
    ]);
    $violations = $drupalEmailAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Disposable provider validation should pass with a non-disposable email address.');
  }

  /**
   * Test disposable email provider list customization.
   *
   * MUST be a separate test because the list is statically cached by the 3rd
   * party library.
   */
  public function testDisposableProviderListCustomization(): void {
    // User that should pass defaults.
    $accountName = $this->randomMachineName();
    $drupalEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@drupal.org',
    ]);

    // User that should fail disposable email validation when using fetched
    // lists.
    $disposableEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@mailinator.com',
    ]);

    // Turn the test on and customize the list.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('rules.disposable', 1)
      ->set('domain_lists.disposable', ['drupal.org'])
      ->save();

    // Run validation.
    $violations = $drupalEmailAccount->validate();
    $this->assertEquals(1, $violations->count(), 'Custom disposable provider validation should fail with an email using a provider on the custom disposable provider list.');

    // Turn on local-only validation.
    $config->set('local_list_only.disposable', TRUE)->save();
    $violations = $disposableEmailAccount->validate();
    $this->assertEquals(0, $violations->count(), 'Disposable provider validation should pass with an email using a disposable email provider that is not in the custom list.');
  }

  /**
   * Test banned email provider.
   */
  public function testBannedProviderValidation(): void {
    // User that should pass defaults.
    $accountName = $this->randomMachineName();
    $drupalEmailAccount = User::create([
      'name' => $accountName,
      'mail' => $accountName . '@drupal.org',
    ]);

    // Turn the test on and set the list. Don't check for 'test is skipped'
    // because of static caching of lists.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('rules.banned', 1)
      ->set('domain_lists.banned', ['drupal.org'])
      ->save();

    // Run validation.
    $violations = $drupalEmailAccount->validate();
    $this->assertEquals(1, $violations->count(), 'Banned provider validation should fail with an email using a domain on the custom banned list.');
    $this->assertEquals('Emails using this domain are not allowed', $violations[0]->getMessage());

    // Change the error message.
    $newError = $this->randomMachineName();
    $config->set('error_messages.banned', $newError)->save();
    $violations = $drupalEmailAccount->validate();
    $this->assertEquals($newError, $violations[0]->getMessage());
  }

}
