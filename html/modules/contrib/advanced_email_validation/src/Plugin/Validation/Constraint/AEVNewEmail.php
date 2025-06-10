<?php

namespace Drupal\advanced_email_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check an email address in a new entity with advanced_email_validation rules.
 *
 * @Constraint(
 *   id = "AEVNewEmail",
 *   label = @Translation("Advanced Email Validation for new entities", context = "Validation"),
 *   type = "string"
 * )
 */
class AEVNewEmail extends Constraint {

  /**
   * Default error message, only used as a fallback for configuration problems.
   *
   * @var string
   */
  public $defaultError = 'Not a valid email address';

}
