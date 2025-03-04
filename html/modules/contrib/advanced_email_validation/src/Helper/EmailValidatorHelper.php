<?php

namespace Drupal\advanced_email_validation\Helper;

use Drupal\advanced_email_validation\AdvancedEmailValidator;

/**
 * Helper class email validator methods.
 */
class EmailValidatorHelper {

  /**
   * Provides the variable content for the form build.
   */
  public static function getValidationChecks(): array {

    $libraryIntro = t('Advanced email validation is built using the
      <a href="@library">stymiee/email-validator library</a>, which fetches
      lists of disposable and free domains from the internet. If you want to
      <strong>add</strong> more domains to this list <em>for this site only</em>
      - e.g. if you discover a domain that is not included - add one domain per
      line in this field.',
      ['@library' => 'https://github.com/stymiee/email-validator']);

    return [
      AdvancedEmailValidator::BASIC => [
        'settings_title' => t('Basic validation settings'),
        'default_error_message' => t('Not a valid email address'),
        'error_description' => t('Before any other check, the library in
          use makes sure the value is in a valid format. Set an error message
          for when this check fails.'),
      ],

      AdvancedEmailValidator::MX_LOOKUP => [
        'rule_title' => t('Valid (uses an MX lookup)'),
        'settings_title' => t('MX lookup validation settings'),
        'default_error_message' => t('Not a valid email address'),
      ],

      AdvancedEmailValidator::DISPOSABLE_DOMAIN => [
        'rule_title' => t('Not a disposable email provider like mailinator.com'),
        'settings_title' => t('Disposable domain validation settings'),
        'default_error_message' => t('Disposable emails are not allowed'),
        'domain_list' => [
          'title' => t('Additional disposable domains'),
          'description' => $libraryIntro,
        ],
        'local_list_only' => [
          'title' => t('Use local domain list only'),
          'description' => t('Disposable domain validation will be limited to
            the domains you provide above.'),
        ],
      ],

      AdvancedEmailValidator::FREE_DOMAIN => [
        'rule_title' => t('Not a public/free email provider like gmail.com'),
        'settings_title' => t('Free domain validation settings'),
        'default_error_message' => t('Free public email providers are not allowed'),
        'domain_list' => [
          'title' => t('Additional free domains'),
          'description' => $libraryIntro,
        ],
        'local_list_only' => [
          'title' => t('Use local domain list only'),
          'description' => t('Free domain validation will be limited to
            the domains you provide above.'),
        ],
      ],

      AdvancedEmailValidator::BANNED_DOMAIN => [
        'rule_title' => t('Not in your custom list of banned email providers'),
        'settings_title' => t('Banned domain validation settings'),
        'default_error_message' => t('Emails using this domain are not allowed'),
        'domain_list' => [
          'title' => t('Banned domains'),
          'description' => t('Enter one domain per line to create a custom list
           of banned domains. You may use "*" as a wildcard in this list, e.g.
           *.example.org will pick up bar.example.org as well as
           foo.bar.example.org and so on.'),
        ],
      ],
    ];
  }

  /**
   * Convert boolean array to valid options array.
   *
   * Helper method to convert an array containing boolean values to a valid
   * "checkboxes" form element options array.
   *
   * The Drupal "checkboxes" form element does not handle a boolean schema
   * definition well, so we need to convert the values so they are getting
   * displayed properly.
   *
   * @param array $checkboxesBoolConfig
   *   An array containing boolean values.
   *
   * @return array
   *   An array containing valid checkbox options.
   */
  public static function convertToCheckboxesOptions(array $checkboxesBoolConfig): array {
    $checkboxOptionValues = [];
    foreach ($checkboxesBoolConfig as $checkboxKey => $checkboxBoolValue) {
      if ($checkboxBoolValue) {
        $checkboxOptionValues[$checkboxKey] = (string) $checkboxKey;
      }
      else {
        $checkboxOptionValues[$checkboxKey] = 0;
      }
    }
    return $checkboxOptionValues;
  }

}
