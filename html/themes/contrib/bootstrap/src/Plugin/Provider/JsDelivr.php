<?php

namespace Drupal\bootstrap\Plugin\Provider;

use Drupal\bootstrap\Bootstrap;
use Drupal\Component\Utility\NestedArray;

/**
 * The "jsdelivr" CDN provider plugin.
 *
 * @ingroup plugins_provider
 *
 * @BootstrapProvider(
 *   id = "jsdelivr",
 *   label = @Translation("jsDelivr"),
 *   themes = { },
 *   versions = { },
 * )
 */
class JsDelivr extends ProviderBase {

  /**
   * Extracts theme information from files provided by the jsDelivr API.
   *
   * This will place the raw files into proper "css", "js" and "min" arrays
   * (if they exist) and prepends them with a base URL provided.
   *
   * @param array $files
   *   An array of files to process.
   * @param string $base_url
   *   The base URL each one of the $files are relative to, this usually
   *   should also include the version path prefix as well.
   *
   * @return array
   *   An associative array containing the following keys, if there were
   *   matching files found:
   *   - css
   *   - js
   *   - min:
   *     - css
   *     - js
   */
  protected function extractThemes(array $files, $base_url = '') {
    $themes = [];
    foreach ($files as $file) {
      preg_match('`([^/]*)/bootstrap(-theme)?(\.min)?\.(js|css)$`', $file, $matches);
      if (!empty($matches[1]) && !empty($matches[4])) {
        $path = $matches[1];
        $min = $matches[3];
        $filetype = $matches[4];

        // Determine the "theme" name.
        if ($path === 'css' || $path === 'js') {
          $theme = 'bootstrap';
          $title = (string) t('Bootstrap');
        }
        else {
          $theme = $path;
          $title = ucfirst($path);
        }
        if ($matches[2]) {
          $theme = 'bootstrap_theme';
          $title = (string) t('Bootstrap Theme');
        }

        $themes[$theme]['title'] = $title;
        if ($min) {
          $themes[$theme]['min'][$filetype][] = "$base_url/" . ltrim($file, '/');
        }
        else {
          $themes[$theme][$filetype][] = "$base_url/" . ltrim($file, '/');
        }
      }
    }
    return $themes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssets($types = NULL) {
    $this->assets = [];
    $error = !empty($provider['error']);
    $version = $error ? Bootstrap::FRAMEWORK_VERSION : $this->theme->getSetting('cdn_jsdelivr_version');
    $theme = $error ? 'bootstrap' : $this->theme->getSetting('cdn_jsdelivr_theme');
    if (isset($this->pluginDefinition['themes'][$version][$theme])) {
      $this->assets = $this->pluginDefinition['themes'][$version][$theme];
    }
    return parent::getAssets($types);
  }

  /**
   * {@inheritdoc}
   *
   * Due to the complex nature of how the existing Provider APIs work and the
   * changes made to the JsDelivr API, a new method was needed to extract the
   * appropriate JSON and URLs to convert the new API data structure into the
   * previous API data structure.
   *
   * @see https://www.drupal.org/project/bootstrap/issues/2657138
   */
  public function processDefinition(array &$definition, $plugin_id) {
    $json = [];
    foreach (['bootstrap', 'bootswatch'] as $package) {
      $data = ['name' => $package, 'assets' => []];
      $latest = '0.0.0';
      $versions = [];
      $packageJson = (array) $this->requestJson("https://data.jsdelivr.com/v1/package/npm/$package");
      $packageJson = $packageJson + ['versions' => []];
      foreach ($packageJson['versions'] as $key => $version) {
        // Skip irrelevant versions.
        if (!preg_match('/^' . substr(Bootstrap::FRAMEWORK_VERSION, 0, 1) . '\.\d+\.\d+$/', $version)) {
          continue;
        }
        $versionJson = $this->requestJson("https://data.jsdelivr.com/v1/package/npm/$package@$version/flat");

        // Skip empty files.
        if (empty($versionJson['files'])) {
          continue;
        }

        $versions[] = $version;
        if (version_compare($latest, $version) === -1) {
          $latest = $version;
        }

        $asset = ['files' => [], 'version' => $version];
        foreach ($versionJson['files'] as $file) {
          // Skip old bootswatch file structure.
          if ($package === 'bootswatch' && preg_match('`^/2|/bower_components`', $file['name'], $matches)) {
            continue;
          }
          preg_match('`([^/]*)/bootstrap(-theme)?(\.min)?\.(js|css)$`', $file['name'], $matches);
          if (!empty($matches[1]) && !empty($matches[4])) {
            $asset['files'][] = $file['name'];
          }
        }
        $data['assets'][] = $asset;
      }
      $data['lastversion'] = $latest;
      $data['versions'] = $versions;
      $json[] = $data;
    }

    $this->processApi($json, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function processApi(array $json, array &$definition) {
    $definition['description'] = t('<p><a href=":jsdelivr" target="_blank">jsDelivr</a> is a free multi-CDN infrastructure that uses <a href=":maxcdn" target="_blank">MaxCDN</a>, <a href=":cloudflare" target="_blank">Cloudflare</a> and many others to combine their powers for the good of the open source community... <a href=":jsdelivr_about" target="_blank">read more</a></p>', [
      ':jsdelivr' => 'https://www.jsdelivr.com',
      ':jsdelivr_about' => 'https://www.jsdelivr.com/about',
      ':maxcdn' => 'https://www.maxcdn.com',
      ':cloudflare' => 'https://www.cloudflare.com',
    ]);

    // Extract the raw asset files from the JSON data for each framework.
    $libraries = [];
    if ($json) {
      foreach ($json as $data) {
        if ($data['name'] === 'bootstrap' || $data['name'] === 'bootswatch') {
          foreach ($data['assets'] as $asset) {
            if (preg_match('/^' . substr(Bootstrap::FRAMEWORK_VERSION, 0, 1) . '\.\d\.\d$/', $asset['version'])) {
              $libraries[$data['name']][$asset['version']] = $asset['files'];
            }
          }
        }
      }
    }

    // If the main bootstrap library could not be found, then provide defaults.
    if (!isset($libraries['bootstrap'])) {
      $definition['error'] = TRUE;
      $definition['versions'][Bootstrap::FRAMEWORK_VERSION] = Bootstrap::FRAMEWORK_VERSION;
      $definition['themes'][Bootstrap::FRAMEWORK_VERSION] = [
        'bootstrap' => [
          'title' => (string) t('Bootstrap'),
          'css' => ['https://cdn.jsdelivr.net/npm/bootstrap@' . Bootstrap::FRAMEWORK_VERSION . '/dist/css/bootstrap.css'],
          'js' => ['https://cdn.jsdelivr.net/npm/bootstrap@' . Bootstrap::FRAMEWORK_VERSION . '/dist/js/bootstrap.js'],
          'min' => [
            'css' => ['https://cdn.jsdelivr.net/npm/bootstrap@' . Bootstrap::FRAMEWORK_VERSION . '/dist/css/bootstrap.min.css'],
            'js' => ['https://cdn.jsdelivr.net/npm/bootstrap@' . Bootstrap::FRAMEWORK_VERSION . '/dist/js/bootstrap.min.js'],
          ],
        ],
      ];
      return;
    }

    // Populate the provider array with the versions and themes available.
    foreach (array_keys($libraries['bootstrap']) as $version) {
      $definition['versions'][$version] = $version;

      if (!isset($definition['themes'][$version])) {
        $definition['themes'][$version] = [];
      }

      // Extract Bootstrap themes.
      $definition['themes'][$version] = NestedArray::mergeDeep($definition['themes'][$version], $this->extractThemes($libraries['bootstrap'][$version], "https://cdn.jsdelivr.net/npm/bootstrap@$version"));

      // Extract Bootswatch themes.
      if (isset($libraries['bootswatch'][$version])) {
        $definition['themes'][$version] = NestedArray::mergeDeep($definition['themes'][$version], $this->extractThemes($libraries['bootswatch'][$version], "https://cdn.jsdelivr.net/npm/bootswatch@$version"));
      }
    }

    // Post process the themes to fill in any missing assets.
    foreach (array_keys($definition['themes']) as $version) {
      foreach (array_keys($definition['themes'][$version]) as $theme) {
        // Some themes actually require Bootstrap framework assets to still
        // function properly.
        if ($theme !== 'bootstrap') {
          foreach (['css', 'js'] as $type) {
            // Bootswatch themes include the Bootstrap framework in their CSS.
            // Skip the CSS portions.
            if ($theme !== 'bootstrap_theme' && $type === 'css') {
              continue;
            }
            if (!isset($definition['themes'][$version][$theme][$type]) && !empty($definition['themes'][$version]['bootstrap'][$type])) {
              $definition['themes'][$version][$theme][$type] = [];
            }
            $definition['themes'][$version][$theme][$type] = NestedArray::mergeDeep($definition['themes'][$version]['bootstrap'][$type], $definition['themes'][$version][$theme][$type]);
            if (!isset($definition['themes'][$version][$theme]['min'][$type]) && !empty($definition['themes'][$version]['bootstrap']['min'][$type])) {
              $definition['themes'][$version][$theme]['min'][$type] = [];
            }
            $definition['themes'][$version][$theme]['min'][$type] = NestedArray::mergeDeep($definition['themes'][$version]['bootstrap']['min'][$type], $definition['themes'][$version][$theme]['min'][$type]);
          }
        }
        // Some themes do not have a non-minified version, clone them to the
        // "normal" css/js arrays to ensure that the theme still loads if
        // aggregation (minification) is disabled.
        foreach (['css', 'js'] as $type) {
          if (!isset($definition['themes'][$version][$theme][$type]) && isset($definition['themes'][$version][$theme]['min'][$type])) {
            $definition['themes'][$version][$theme][$type] = $definition['themes'][$version][$theme]['min'][$type];
          }
        }
      }
    }
  }

}
