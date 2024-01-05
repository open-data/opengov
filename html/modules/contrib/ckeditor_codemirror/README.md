# CKEditor CodeMirror

CKEditor CodeMirror adds syntax highlighting for "Source View" in
CKEditor WYSIWYG editor.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/ckeditor_codemirror).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/ckeditor_codemirror).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires the following modules:

- [CodeMirror v5](https://www.npmjs.com/package/codemirror/v/version5)
  (CodeMirror v6 is **not supported**.)
- [CKEditor 5 CodeMirror plugin](https://www.npmjs.com/package/@cdubz/ckeditor5-source-editing-codemirror)

These requirements must be located in the `libraries` directory of the Drupal
installation:

- `/libraries/codemirror`
- `/libraries/ckeditor5-source-editing-codemirror`

A `composer.libraries.json` file is provided for use with the
[Composer Merge Plugin](https://github.com/wikimedia/composer-merge-plugin).


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


# Configuration

1. Go to **Administration » Configuration » Content authoring » Text formats
   and editors** (`admin/config/content/formats`).
2. Click **Configure** for any text format using CKEditor as the text editor.
3. Under **CKEditor plugin settings**, click **CodeMirror** and check **Enable
   CodeMirror source view syntax highlighting**. Make sure that the current
   toolbar has the `"Source"` button. Adjust other settings as desired.
4. Scroll down and click **Save configuration**.
5. Go to node create/edit page, choose the text format with CodeMirror plugin.
   Press the "Source" button.


## Maintainers

- Plazik - [Plazik](https://www.drupal.org/u/plazik)
- Christopher C. Wells - [wells](https://www.drupal.org/u/wells)
