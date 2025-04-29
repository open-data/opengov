<?php

namespace Drupal\fontawesome_iconpicker_widget\Commands;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

/**
 * A Drush commandfile for Font Awesome module.
 */
class FontawesomeIconPickerCommands extends DrushCommands {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected LibraryDiscoveryInterface $libraryDiscovery,
    protected FileSystemInterface $fileSystem,
    protected ArchiverManager $archiverManager,
    protected Client $httpClient,
  ) {
    parent::__construct();
  }

  /**
   * Downloads the required Fontawesome Iconpicker library.
   *
   * @param string $path
   *   Optional path to module. If omitted Drush will use the default location.
   *
   * @command fa:download-iconpicker
   * @aliases fa-download-iconpicker
   */
  public function download($path = '') {

    if (empty($path)) {
      // We have dependencies on libraries module so no need to check for that.
      // @todo any way to get path for libraries directory?
      // Just in case if it is site specific? e.g. sites/domain.com/libraries ?
      $path = DRUPAL_ROOT . '/libraries/fonticonpicker--fonticonpicker';
    }

    // Create the path if it does not exist yet. Added substr check for
    // preventing any wrong attempts or hacks !
    if (substr($path, -30) == 'fonticonpicker--fonticonpicker' && !is_dir($path)) {
      $this->fileSystem->mkdir($path);
    }
    if (is_dir($path . '/dist')) {
      $this->logger()->notice(dt('IconPicker already present at @path. No download required.', ['@path' => $path]));
      return;
    }

    // Load the Font Awesome defined library.
    if ($iconpicker_library = $this->libraryDiscovery->getLibraryByName('fontawesome_iconpicker_widget', 'fonticonpicker')) {

      // Download the file.
      $url = $iconpicker_library['remote'];
      $destination = tempnam(sys_get_temp_dir(), 'file.') . "tar.gz";

      try {
        $data = (string) $this->httpClient->get($url)->getBody();
        $this->fileSystem->saveData($data, $destination, FileExists::Replace);
      }
      catch (TransferException $exception) {
        $this->logger()->error(dt('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
      }
      catch (FileException | InvalidStreamWrapperException $e) {
        $this->logger()->error(dt('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
      }

      if (!file_exists($destination)) {
        // Remove the directory.
        $this->fileSystem->rmdir($path);
        $this->logger()->error(dt('Drush was unable to download the fontIconPicker library from @remote.', [
          '@remote' => $iconpicker_library['remote'],
        ]));
        return;
      }
      $this->fileSystem->move($destination, $path . '/fontIconPicker.zip');
      if (!file_exists($path . '/fontIconPicker.zip')) {
        // Remove the directory where we tried to install.
        $this->fileSystem->rmdir($path);
        $this->logger()->error(dt('Error: unable to download fontIconPicker library from @remote', [
          '@remote' => $iconpicker_library['remote'],
        ]));
        return;
      }

      // Unzip the file.
      /** @var \Drupal\Core\Archiver\ArchiverInterface $zipFile */
      $zipFile = $this->archiverManager->getInstance(['filepath' => $path . '/fontIconPicker.zip']);
      $zipFile->extract($path);

      // Remove the downloaded zip file.
      $this->fileSystem->unlink($path . '/fontIconPicker.zip');

      // Success.
      $this->logger()->notice(dt('fontIconPicker library has been successfully downloaded to @path.', [
        '@path' => $path,
      ]));
    }
    else {
      $this->logger()->error(dt('Drush was unable to load the fontIconPicker) library'));
    }
  }

}
