<?php

namespace Drupal\search_api_solr_legacy\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Search API Solr Legacy hooks.
 */
final class SearchApiSolrLegacyHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_search_api_server_form_alter')]
  public function formSearchApiServerFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // We need to restrict by form ID here because this function is also called
    // via hook_form_BASE_FORM_ID_alter (which is wrong, e.g. in the case of the
    // form ID search_api_field_config).
    if (
      in_array($form_id, ['search_api_server_form', 'search_api_server_edit_form']) &&
      isset($form['backend_config']['connector_config']['workarounds']['solr_version']['#options'])
    ) {
      $versions = $form['backend_config']['connector_config']['workarounds']['solr_version']['#options'] + [
        '3.6' => '3.x',
        '4.5' => '4.x',
        '5' => '5.x',
      ];
      ksort($versions);
      $form['backend_config']['connector_config']['workarounds']['solr_version']['#options'] = $versions;
    }
  }

}
