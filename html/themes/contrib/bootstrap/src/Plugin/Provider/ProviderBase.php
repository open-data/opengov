<?php

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Plugin\ProviderManager;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

/**
 * CDN provider base class.
 *
 * @ingroup plugins_provider
 */
class ProviderBase extends PluginBase implements ProviderInterface {

  /**
   * The currently set assets.
   *
   * @var array
   */
  protected $assets = [];

  /**
   * The versions supplied by the CDN provider.
   *
   * @var array
   */
  protected $versions;

  /**
   * {@inheritdoc}
   */
  public function getApi() {
    return $this->pluginDefinition['api'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAssets($types = NULL) {
    // Immediately return if there are no assets.
    if (!$this->assets) {
      return $this->assets;
    }

    $assets = [];

    // If no type is set, return all CSS and JS.
    if (!isset($types)) {
      $types = ['css', 'js'];
    }
    $types = is_array($types) ? $types : [$types];

    // Ensure default arrays exist for the requested types.
    foreach ($types as $type) {
      $assets[$type] = [];
    }

    // Retrieve the system performance config.
    $config = \Drupal::config('system.performance');

    // Iterate over each type.
    foreach ($types as $type) {
      $min = $config->get("$type.preprocess");
      $files = $min && isset($this->assets['min'][$type]) ? $this->assets['min'][$type] : (isset($this->assets[$type]) ? $this->assets[$type] : []);
      foreach ($files as $asset) {
        $data = [
          'data' => $asset,
          'type' => 'external',
        ];
        // CSS library assets use "SMACSS" categorization, assign it to "base".
        if ($type === 'css') {
          $assets[$type]['base'][$asset] = $data;
        }
        else {
          $assets[$type][$asset] = $data;
        }
      }
    }

    return count($types) === 1 ? $assets[$types[0]] : $assets;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'] ?: $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getThemes() {
    return $this->pluginDefinition['themes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersions() {
    return $this->pluginDefinition['versions'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasError() {
    return $this->pluginDefinition['error'];
  }

  /**
   * {@inheritdoc}
   */
  public function isImported() {
    return $this->pluginDefinition['imported'];
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(array &$definition, $plugin_id) {
    $provider_path = ProviderManager::FILE_PATH;
    file_prepare_directory($provider_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    // Process API data.
    if ($api = $this->getApi()) {
      // Use manually imported API data, if it exists.
      if (file_exists("$provider_path/$plugin_id.json") && ($imported_data = file_get_contents("$provider_path/$plugin_id.json"))) {
        $definition['imported'] = TRUE;
        try {
          $json = Json::decode($imported_data);
        }
        catch (\Exception $e) {
          // Intentionally left blank.
        }
      }
      // Otherwise, attempt to request API data if the provider has specified
      // an "api" URL to use.
      else {
        $json = $this->requestJson($api);
      }

      if (!isset($json)) {
        $json = [];
        $definition['error'] = TRUE;
      }

      $this->processApi($json, $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processApi(array $json, array &$definition) {}

  /**
   * Retrieves JSON from a URI.
   *
   * @param string $uri
   *   The URI to retrieve JSON from.
   * @param array $options
   *   The options to pass to the HTTP client.
   *
   * @return array|null
   *   The requested JSON array or NULL if an error occurred.
   */
  protected function requestJson($uri, array $options = []) {
    $json = NULL;

    $options += [
      'method' => 'GET',
      'headers' => [
        'User-Agent' => 'Drupal Bootstrap (https://www.drupal.org/project/bootstrap)',
      ],
    ];

    /** @var \GuzzleHttp\Client $client */
    $client = \Drupal::service('http_client_factory')->fromOptions($options);
    $request = new Request($options['method'], $uri);
    try {
      $response = $client->send($request, $options);
      if ($response->getStatusCode() == 200) {
        $contents = $response->getBody(TRUE)->getContents();
        $json = Json::decode($contents);
      }
    }
    catch (GuzzleException $e) {
      // Intentionally left blank.
    }
    catch (\Exception $e) {
      // Intentionally left blank.
    }
    return $json;
  }

}
