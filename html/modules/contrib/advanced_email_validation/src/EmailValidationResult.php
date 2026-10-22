<?php

namespace Drupal\advanced_email_validation;

use EmailValidator\EmailValidator;

/**
 * The outcome of validating an email address.
 *
 * @see \Drupal\advanced_email_validation\AdvancedEmailValidatorInterface::validateEmail()
 */
class EmailValidationResult {

  /**
   * Constructs an EmailValidationResult.
   *
   * @param int $errorCode
   *   The validation error code (0 = no error). See
   *   \Drupal\advanced_email_validation\AdvancedEmailValidatorInterface::validateEmail().
   * @param string $message
   *   The message to show when validation failed, or an empty string when the
   *   address is valid.
   */
  public function __construct(
    public readonly int $errorCode,
    public readonly string $message,
  ) {}

  /**
   * Whether the email address passed validation.
   *
   * @return bool
   *   TRUE if there was no validation error.
   */
  public function isValid(): bool {
    return $this->errorCode === EmailValidator::NO_ERROR;
  }

}
