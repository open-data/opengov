<?php

namespace Drupal\advanced_email_validation;

/**
 * Interface for the Advanced Email Validator service.
 */
interface AdvancedEmailValidatorInterface {

  /**
   * Keys used in configuration.
   */
  const BASIC = 'basic';

  const MX_LOOKUP = 'mx_lookup';

  const DISPOSABLE_DOMAIN = 'disposable';

  const FREE_DOMAIN = 'free';

  const BANNED_DOMAIN = 'banned';

  /**
   * Validate an email address.
   *
   * Uses the configuration set in the configuration UI by default.
   *
   * @param string $email
   *   The email address to be validated.
   * @param array $configOverrides
   *   Optional configuration overrides
   *   - checkMxRecords: boolean, test the email is from a valid provider
   *   - checkBannedListedEmail: boolean, test the email isn't on your banned
   *       list
   *   - checkDisposableEmail: boolean, test the email isn't from a disposable
   *       address provider like mailinator
   *   - checkFreeEmail: boolean, test the email isn't from a free address
   *       provider like gmail
   *   - bannedList: array, list of domains to ban
   *   - disposableList: array, list of additional disposable domains
   *   - freeList: array, list of additional free domains.
   *
   * @return int
   *   The returned status of the validation test per
   *   \EmailValidator\EmailValidator:
   *   NO_ERROR = 0;
   *   FAIL_BASIC = 1;
   *   FAIL_MX_RECORD = 2;
   *   FAIL_BANNED_DOMAIN = 3;
   *   FAIL_DISPOSABLE_DOMAIN = 4;
   *   FAIL_FREE_PROVIDER = 5;
   */
  public function validate(string $email, array $configOverrides = []): int;

  /**
   * Translates error codes into Drupal error messages that can be localized.
   *
   * @param int $errorCode
   *   @see AdvancedEmailValidatorInterface::validate()
   * @param array $errorMessages
   *   Optional error messages overrides.
   *
   * @return string
   *   The localized error message, returns an empty string if no match.
   */
  public function errorMessageFromCode(int $errorCode, array $errorMessages = []): string;

}
