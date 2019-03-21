<?php

namespace Drupal\feeds_ex\Utility;

use RuntimeException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Various helpers for handling JSON.
 */
class JsonUtility {

  use StringTranslationTrait;

  /**
   * Translates an error message.
   *
   * @param int $error
   *   The JSON error.
   *
   * @return string
   *   The JSON parsing error message.
   */
  public function translateError($error) {
    if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
      switch ($error) {
        case JSON_ERROR_RECURSION:
          return 'One or more recursive references in the value to be encoded';

        case JSON_ERROR_INF_OR_NAN:
          return 'One or more NAN or INF values in the value to be encoded';

        case JSON_ERROR_UNSUPPORTED_TYPE:
          return 'A value of a type that cannot be encoded was given';
      }
    }

    switch ($error) {
      case JSON_ERROR_UTF8:
        return 'Malformed UTF-8 characters, possibly incorrectly encoded';

      case JSON_ERROR_NONE:
        return 'No error has occurred';

      case JSON_ERROR_DEPTH:
        return 'The maximum stack depth has been exceeded';

      case JSON_ERROR_STATE_MISMATCH:
        return 'Invalid or malformed JSON';

      case JSON_ERROR_CTRL_CHAR:
        return 'Control character error, possibly incorrectly encoded';

      case JSON_ERROR_SYNTAX:
        return 'Syntax error';

      default:
        return 'Unknown error';
    }
  }

  /**
   * Decodes a JSON string into an array.
   *
   * @param string $json
   *   A JSON string.
   *
   * @return array
   *   A PHP array.
   *
   * @throws RuntimeException
   *   Thrown if the encoded JSON does not result in an array.
   */
  public function decodeJsonArray($json) {
    $parsed = Json::decode($json);

    if (!is_array($parsed)) {
      throw new RuntimeException($this->t('The JSON is invalid.'));
    }

    return $parsed;
  }

}
