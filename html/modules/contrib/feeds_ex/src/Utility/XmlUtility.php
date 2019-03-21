<?php

namespace Drupal\feeds_ex\Utility;

use DOMDocument;
use RuntimeException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Simple XML helpers.
 */
class XmlUtility {

  use StringTranslationTrait;

  /**
   * Creates an HTML document.
   *
   * @param string $source
   *   The string containing the HTML.
   * @param int $options
   *   (optional) Bitwise OR of the libxml option constants. Defaults to 0.
   *
   * @return \DOMDocument
   *   The newly created DOMDocument.
   *
   * @throws \RuntimeException
   *   Thrown if there is a fatal error parsing the XML.
   */
  public function createHtmlDocument($source, $options = 0) {
    // Fun hack to force parsing as utf-8.
    $source = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n" . $source;
    $document = $this->buildDomDocument();
    // Pass in options if available.
    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
      $options = $options | LIBXML_NOENT | LIBXML_NONET | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0;

      if (version_compare(LIBXML_DOTTED_VERSION, '2.7.0', '>=')) {
        $options = $options | LIBXML_PARSEHUGE;
      }
      $success = $document->loadHTML($source, $options);
    }
    else {
      $success = $document->loadHTML($source);
    }

    if (!$success) {
      throw new RuntimeException($this->t('There was an error parsing the HTML document.'));
    }
    return $document;
  }

  /**
   * Builds a DOMDocument setting some default values.
   *
   * @return \DOMDocument
   *   A new DOMDocument.
   */
  protected function buildDomDocument() {
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->strictErrorChecking = FALSE;
    $document->resolveExternals = FALSE;
    // Libxml specific.
    $document->substituteEntities = FALSE;
    $document->recover = TRUE;
    $document->encoding = 'UTF-8';
    return $document;
  }

}
