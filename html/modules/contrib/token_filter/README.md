# Token Filter

This is a very simple module to make global token values available as an input
filter. The module is integrated with [Token](https://drupal.org/project/token)
module.

- For a full description of the module visit:
  [Project Page](https://www.drupal.org/project/token_filter).

- To submit bug reports and feature suggestions, or to track changes visit:
  [Issue Queue](https://www.drupal.org/project/issues/token_filter).


## Contents of this file

- Usage/Requirements
- Installation
- Tokens typically available


## Usage/Requirements

Install the module as any other module. Visit the text format administration
page at /admin/config/content/formats/filters and edit a text format. Check the
'Replaces global and entity tokens with their values' filter and save the text
format.

When editing a form where this text format is used in a field, you can type
global tokens that will be replaced when the filed is rendered.

Additionally, if the [Token](https://drupal.org/project/token) module is enabled,
the token browser is available. You can pick-up the desired token from the
browser by clicking 'Browse available tokens'.


## Installation

Install the Token Filter as you would normally install a
contributed Drupal module. for further information visit:
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-modules).


## Tokens typically available

Tokens in the next groups are available on a standard installation:

- random
- current-date
- site
- current-page
- current-user
