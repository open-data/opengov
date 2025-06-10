# reCAPTCHA v3

This module enables you to easily configure reCaptcha v3
and a fallback challenge (captcha/recaptcha v2 e.g).
In case user fails reCaptcha v3,
he can be prompted with an additional challenge to prove.
This is an ideal way to maximize security without any user friction.

We no more rely on the reCAPTCHA module for the use of the `recaptcha-php`
library which is included in this module, and make use of
Composer instead of keeping a duplicating code.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/recaptcha_v3).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/recaptcha_v3).


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Maintainers


## Requirements

This module requires the following module:

- [CAPTCHA](https://www.drupal.org/project/captcha)

This module requires the following library:

- [google/recaptcha](https://github.com/google/recaptcha)


## Recommended modules

[reCAPTCHA](https://www.drupal.org/project/recaptcha):
When enabled, reCAPTCHA v2 becomes available as fallback challenge.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

If not using Composer,
install the [google/recaptcha](https://github.com/google/recaptcha) library.


## Configuration

1. Register reCAPTCHA v3 keys (https://www.google.com/recaptcha/admin/create).

   - The documentation for Google reCaptcha V3

   - The documentation can be found here
     https://developers.google.com/recaptcha/docs/v3),
     with information regarding keys registration.

2. Create at least one action:

   - Populate action name

   - Choose score threshold

   - Select action on user verification fail

3. Use the action you created above as a challenge in captcha form settings.
