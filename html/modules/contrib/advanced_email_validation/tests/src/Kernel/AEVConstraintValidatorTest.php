<?php

namespace Drupal\Tests\advanced_email_validation\Kernel;

use Drupal\advanced_email_validation_test\TestFieldNames;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for AEVChangedEmailValidator behavior on a custom field.
 *
 * @group advanced_email_validation
 */
#[RunTestsInSeparateProcesses]
class AEVConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
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
    $this->container->get('module_installer')->install(['advanced_email_validation_test']);
  }

  /**
   * Updating without changing the custom email should not trigger validation.
   */
  public function testFieldCreate(): void {
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@drupal.org',
      TestFieldNames::CREATE => $this->randomMachineName() . '@invalidinvalidinvalid.com',
    ]);
    $violations = $account->validate();
    $this->assertEquals(1, $violations->count(), 'Validation should fail based on MX lookup.');

    // Save the account with the invalid email so we can try again.
    $account->save();

    // Set to alternate invalid (.org) and validate.
    $account->set(TestFieldNames::CREATE, $this->randomMachineName() . '@invalidinvalidinvalid.org');
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass because the entity is not new.');
    $account->save();
  }

  /**
   * Updating without changing the custom email should not trigger validation.
   */
  public function testFieldUpdate(): void {
    $invalidEmail = $this->randomMachineName() . '@invalidinvalidinvalid.com';
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@drupal.org',
      TestFieldNames::UPDATE => $invalidEmail,
    ]);
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass because the entity is new.');

    // Save the account with the invalid email so we can try again.
    $account->save();

    // Set the same email again and validate.
    $account->set(TestFieldNames::UPDATE, $invalidEmail);
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass because the email was not changed.');
    $account->save();

    // Change to another valid email address.
    $account->set(TestFieldNames::UPDATE, $this->randomMachineName() . '@drupal.org');
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass because the email was changed to a valid address.');
    $account->save();

    // Change to an invalid email address.
    $account->set(TestFieldNames::UPDATE, $invalidEmail);
    $violations = $account->validate();
    $this->assertEquals(1, $violations->count(), 'Validation should fail based on MX lookup.');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage(), 'The AEV error message should be the configured basic error.');
  }

  /**
   * Updating without changing the custom email should not trigger validation.
   */
  public function testFieldCreateAndUpdate(): void {
    $invalidEmail = $this->randomMachineName() . '@invalidinvalidinvalid.com';
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@drupal.org',
      TestFieldNames::BOTH => $invalidEmail,
    ]);
    $violations = $account->validate();
    $this->assertEquals(1, $violations->count(), 'Validation should fail based on MX lookup.');

    // Save the account with the invalid email so we can try again.
    $account->save();

    // Set the same email again and validate.
    $account->set(TestFieldNames::BOTH, $invalidEmail);
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should fail even though the email was not changed.');
    $account->save();

    // Change to another valid email address.
    $account->set(TestFieldNames::BOTH, $this->randomMachineName() . '@drupal.org');
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass because the email was changed to a valid address.');
    $account->save();

    // Change to an invalid email address.
    $account->set(TestFieldNames::BOTH, $invalidEmail);
    $violations = $account->validate();
    $this->assertEquals(1, $violations->count(), 'Validation should fail based on MX lookup.');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage(), 'The AEV error message should be the configured basic error.');
  }

  /**
   * An empty configured error message falls back to the default on update.
   *
   * When a rule is triggered on a changed email but its configured error
   * message is an empty string, the updated-entity validator (AEVChangedEmail)
   * must produce exactly one violation carrying the constraint default, not two
   * violations and not an empty-message violation.
   */
  public function testEmptyErrorMessageFallsBackToDefaultOnUpdate(): void {
    // Create and save a user with a valid email in the update-only field, so
    // the entity is no longer new when we change the field below.
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@drupal.org',
      TestFieldNames::UPDATE => $this->randomMachineName() . '@drupal.org',
    ]);
    $violations = $account->validate();
    $this->assertEquals(0, $violations->count(), 'Validation should pass because the entity is new with a valid address.');
    $account->save();

    // Configure the MX lookup error message to an empty string. MX lookup is
    // enabled by default, and an invalid domain triggers it.
    $config = $this->config('advanced_email_validation.settings');
    $config->set('error_messages.mx_lookup', '')->save();

    // Change the field to a syntactically valid address on an invalid domain,
    // triggering the changed-email validator on the MX lookup rule.
    $account->set(TestFieldNames::UPDATE, $this->randomMachineName() . '@invalidinvalidinvalid.com');
    $violations = $account->validate();
    $this->assertEquals(1, $violations->count(), 'An empty configured error message must yield exactly one violation, not two.');
    $this->assertEquals('Not a valid email address', $violations[0]->getMessage(), 'The empty configured message must fall back to the constraint default.');
  }

}
