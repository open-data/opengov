<?php

namespace Drupal\password_policy;

/**
 * A construct to organize validation of a password policy.
 *
 * @package Drupal\password_policy
 */
class PasswordPolicyValidation {

  protected $error = NULL;
  protected $valid = TRUE;

  /**
   * Set error message and mark as invalid.
   */
  public function setErrorMessage($error) {
    $this->valid = FALSE;
    $this->error = $error;
  }

  /**
   * Output error message.
   *
   * @return string
   *   A message representing the error message of the policy's constraints.
   */
  public function getErrorMessage() {
    return $this->error;
  }

  /**
   * Output validation state.
   *
   * @return bool
   *   Whether or not the policy has an error.
   */
  public function isValid() {
    return $this->valid;
  }

}
