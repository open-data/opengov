# Advanced Email Validation

Uses the open-source stymiee/email-validator library to validate email
addresses using advanced, configurable rules.

**Features**
This module:

- automatically applies the configured rule-set to user email addresses
- allows site builders to customize and translate all of the error messages
  shown to users using the Configuration Translation core module
- exposes a service which pre-configures the library based on current
  configuration for use in development, including localized error messages
- supplies a Webform validation handler that can be added to apply the available
  rules to chosen email or email_confirm fields on any webform, with the option
  to override configuration.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/advanced_email_validation).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/advanced_email_validation).

## Requirements

This module requires the following library which will be automatically installed
by composer when you use composer to install this module:

- [stymiee/email-validator](https://github.com/stymiee/email-validator)

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Configure the validator settings in
Administration > Configuration > People > Advanced email validation settings.
