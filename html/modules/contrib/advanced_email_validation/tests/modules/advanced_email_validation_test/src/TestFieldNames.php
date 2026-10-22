<?php

namespace Drupal\advanced_email_validation_test;

/**
 * Provides shared field names for the advanced_email_validation_test module.
 *
 * This avoids using global constants while allowing reuse in both the module
 * code and related tests.
 */
final class TestFieldNames {

  public const CREATE = 'create_validation_email';
  public const UPDATE = 'update_validation_email';
  public const BOTH = 'both_validation_email';

  /**
   * Returns all field machine names defined for the test module.
   *
   * @return string[]
   *   An array of field machine names.
   */
  public static function all(): array {
    return [
      self::CREATE,
      self::UPDATE,
      self::BOTH,
    ];
  }

}
