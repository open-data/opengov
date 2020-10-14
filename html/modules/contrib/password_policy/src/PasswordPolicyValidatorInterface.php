<?php

namespace Drupal\password_policy;

use Drupal\user\UserInterface;

/**
 * Interface PasswordPolicyValidatorInterface.
 *
 * @package Drupal\password_policy
 */
interface PasswordPolicyValidatorInterface {

  /**
   * Validates the given password.
   *
   * @param string $password
   *   The new password.
   * @param \Drupal\user\UserInterface $user
   *   The current user object.
   * @param array $edited_user_roles
   *   An optional array containing the edited user roles.
   *
   * @return bool
   *   True when the password is valid, else false.
   */
  public function validatePassword(string $password, UserInterface $user, array $edited_user_roles = []): bool;

  /**
   * Builds the password policy constraints table rows.
   *
   * @param string $password
   *   The new password.
   * @param \Drupal\user\UserInterface $user
   *   The current user object.
   * @param array $edited_user_roles
   *   An optional array containing the edited user roles.
   *
   * @return array
   *   An array containing the constraints table rows.
   */
  public function buildPasswordPolicyConstraintsTableRows(string $password, UserInterface $user, array $edited_user_roles = []): array;

}
