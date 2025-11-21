<?php

namespace Drupal\webform\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for webform.
 */
class WebformInstallUpdateHooks {

  /**
   * Implements hook_update_dependencies().
   */
  #[Hook('update_dependencies')]
  public function updateDependencies() {
    // Ensure that system_update_8501() runs before the webform update, so that
    // the new revision_default field is installed in the correct table.
    // @see https://www.drupal.org/project/webform/issues/2958102
    $dependencies['webform'][8099]['system'] = 8501;
    // Ensure that system_update_8805() runs before the webform update, so that
    // the 'path_alias' module is enabled and configured correctly.
    // @see https://www.drupal.org/project/webform/issues/3166248
    $dependencies['webform']['8158']['system'] = 8805;
    return $dependencies;
  }

}
