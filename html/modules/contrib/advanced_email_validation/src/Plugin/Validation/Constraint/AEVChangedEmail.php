<?php

namespace Drupal\advanced_email_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check an updated email against advanced_email_validation module rules.
 *
 * @Constraint(
 *   id = "AEVChangedEmail",
 *   label = @Translation("Advanced Email Validation for updated entities", context = "Validation"),
 *   type = "string"
 * )
 */
class AEVChangedEmail extends Constraint {

  /**
   * Default error message, only used as a fallback for configuration problems.
   *
   * @var string
   */
  public $defaultError = 'Not a valid email address';

}
