<?php

namespace Drupal\search_api_solr\Utility;

use ZipStream\ZipStream;

/**
 * Creates ZipStream instances and headers for config downloads.
 */
class ZipStreamFactory {

  /**
   * Returns a ZipStream instance.
   *
   * @param string $name
   *   The output archive name.
   * @param resource|null $resource
   *   Output stream resource.
   *
   * @return \ZipStream\ZipStream
   *   The ZipStream that contains all configuration files.
   */
  public static function createInstance($name, $resource = NULL): ZipStream {
    if ($resource) {
      return new ZipStream(
        outputStream: $resource,
        enableZip64: FALSE,
        defaultEnableZeroHeader: FALSE,
        httpHeaderCallback: static::sendHttpHeader(...),
      );
    }

    return new ZipStream(
      enableZip64: FALSE,
      defaultEnableZeroHeader: FALSE,
      outputName: $name,
      httpHeaderCallback: static::sendHttpHeader(...),
    );
  }

  /**
   * Sends an HTTP header for ZipStream downloads.
   *
   * Ensures config zip downloads are not stored in shared caches.
   *
   * @param string $header
   *   The header line emitted by ZipStream.
   */
  public static function sendHttpHeader(string $header): void {
    if (0 === stripos($header, 'Cache-Control:')) {
      $header = 'Cache-Control: private';
    }

    header($header, TRUE);
  }

}
