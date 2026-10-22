<?php

namespace Drupal\search_api_solr_log\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\search_api_solr_log\Logger\SolrLogger;

/**
 * Search API Solr Log hooks.
 */
final class SearchApiSolrLogHooks {

  /**
   * Constructs a SearchApiSolrLogHooks object.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $config = $this->configFactory->get('search_api_solr_log.settings');
    try {
      SolrLogger::delete($config->get('days_to_keep') ?? 14);
    }
    catch (\Exception) {
      // Do nothing.
    }
  }

  /**
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $data['search_api_index_search_api_solr_log']['severity']['field']['id'] = 'search_api_solr_log_severity';
    $data['search_api_index_search_api_solr_log']['message']['field']['id'] = 'search_api_solr_log_message';
    $data['search_api_index_search_api_solr_log']['uid']['field']['id'] = 'search_api_solr_log_user';
  }

}
