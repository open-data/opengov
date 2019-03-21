<?php

namespace Drupal\feeds_test_files\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates CSV source files.
 */
class CsvController extends ControllerBase {

  /**
   * Generates an absolute url to the resources folder.
   *
   * @return string
   *   An absolute url to the resources folder, for example:
   *   http://www.example.com/modules/contrib/feeds/tests/resources
   */
  protected function getResourcesUrl() {
    $resources_path = drupal_get_path('module', 'feeds') . '/tests/resources';
    return Url::fromUri('internal:/' . $resources_path)
      ->setAbsolute()
      ->toString();
  }

  /**
   * Outputs a CSV file pointing to files.
   */
  public function files() {
    $assets_url = $this->getResourcesUrl() . '/assets';

    $csv_lines = [
      ['title', 'timestamp', 'file'],
      ['Tubing is awesome', '205200720', $assets_url . '/tubing.jpeg'],
      ['Jeff vs Tom', '428112720', $assets_url . '/foosball.jpeg'],
      ['Attersee', '1151766000', $assets_url . '/attersee.jpeg'],
      ['H Street NE', '1256326995', $assets_url . '/hstreet.jpeg'],
      ['La Fayette Park', '1256326995', $assets_url . '/la fayette.jpeg'],
      ['Attersee 2', '1151766000', $assets_url . '/attersee.JPG'],
    ];

    $csv = '';
    foreach ($csv_lines as $line) {
      $csv .= implode(',', $line) . "\n";
    }

    $response = new Response();
    $response->setContent($csv);
    return $response;
  }

}
