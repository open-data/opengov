<?php

namespace Drupal\fontawesome\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * A Drush commandfile for Font Awesome module.
 */
class FontawesomeCommands extends DrushCommands {

  /**
   * Downloads the required Fontawesome library.
   *
   * @param string $path
   *   Optional path to module. If omitted Drush will use the default location.
   *
   * @command fa:download
   * @aliases fadl,fa-download
   */
  public function download($path = '') {

    // Declare filesystem container.
    $fs = new Filesystem();

    if (empty($path)) {
      // We have dependencies on libraries module so no need to check for that
      // TODO: any way to get path for libraries directory?
      // Just in case if it is site specific? e.g. sites/domain.com/libraries ?
      $path = drush_get_context('DRUSH_DRUPAL_ROOT') . '/libraries/fontawesome';
    }

    // Create the path if it does not exist yet. Added substr check for
    // preventing any wrong attempts or hacks !
    if (substr($path, -11) == 'fontawesome' && !is_dir($path)) {
      $fs->mkdir($path);
    }
    if (is_dir($path . '/css')) {
      $this->logger()->notice(dt('Font Awesome already present at @path. No download required.', ['@path' => $path]));
      return;
    }

    // Load the Font Awesome defined library.
    if ($fontawesome_library = \Drupal::service('library.discovery')->getLibraryByName('fontawesome', 'fontawesome.svg')) {

      // Download the file.
      $client = new Client();
      $destination = tempnam(sys_get_temp_dir(), 'file.') . "tar.gz";
      try {
        $client->get($fontawesome_library['remote'], ['save_to' => $destination]);
      }
      catch (RequestException $e) {
        // Remove the directory.
        $fs->remove($path);
        $this->logger()->error(dt('Drush was unable to download the Font Awesome library from @remote. @exception', [
          '@remote' => $fontawesome_library['remote'],
          '@exception' => $e->getMessage(),
        ], 'error'));
        return;
      }
      $fs->rename($destination, $path . '/fontawesome.zip');
      if (!file_exists($path . '/fontawesome.zip')) {
        // Remove the directory where we tried to install.
        $fs->remove($path);
        $this->logger()->error(dt('Error: unable to download Fontawesome library from @remote', [
          '@remote' => $fontawesome_library['remote'],
        ], 'error'));
        return;
      }

      // Unzip the file.
      $zip = new \ZipArchive();
      $res = $zip->open($path . '/fontawesome.zip');
      if ($res === TRUE) {
        $zip->extractTo($path);
        $zip->close();
      }
      else {
        // Remove the directory.
        $fs->remove($path);
        $this->logger()->error(dt('Error: unable to unzip Fontawesome file.', [], 'error'));
        return;
      }

      // Remove the downloaded zip file.
      $fs->remove($path . '/fontawesome.zip');

      // Move the file.
      $fs->mirror($path . '/fontawesome-free-' . $fontawesome_library['version'] . '-web', $path, NULL, ['override' => TRUE]);
      $fs->remove($path . '/fontawesome-free-' . $fontawesome_library['version'] . '-web');

      // Success.
      $this->logger()->notice(dt('Fontawesome library has been successfully downloaded to @path.', [
        '@path' => $path,
      ], 'success'));
    }
    else {
      $this->logger()->error(dt('Drush was unable to load the Font Awesome library'));
    }
  }

}
