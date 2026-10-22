<?php

namespace Drupal\search_api_solr_admin\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api_solr\SolrBackendInterface;

/**
 * Search API Solr Admin hooks.
 */
final class SearchApiSolrAdminHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'solr_field_analysis' => [
        'variables' => [
          'data' => [],
          'title' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_search_api_server_status_alter')]
  public function formSearchApiServerStatusAlter(&$form, FormStateInterface $form_state, $form_id): void {
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $form['#server'];
    $backend = $server->getBackend();
    if ($backend instanceof SolrBackendInterface && $backend->getSolrConnector()->isCloud()) {
      $form['actions']['delete_collection'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete collection'),
        '#button_type' => 'danger',
        '#submit' => [[self::class, 'submitDeleteCollection']],
      ];
    }
  }

  /**
   * Submit handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @see self::formSearchApiServerStatusAlter()
   */
  public static function submitDeleteCollection(array &$form, FormStateInterface $form_state): void {
    // Redirect to the "delete collection" form.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $form['#server'];
    $form_state->setRedirect('search_api_solr_admin.solr_delete_collection_form', ['search_api_server' => $server->id()]);
  }

}
