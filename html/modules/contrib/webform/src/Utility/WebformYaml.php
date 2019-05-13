<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Serialization\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Provides YAML tidy function.
 */
class WebformYaml implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    $dumper = new Dumper(2);
    $yaml = $dumper->dump($data, PHP_INT_MAX, 0, SymfonyYaml::DUMP_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

    // Remove return after array delimiter.
    $yaml = preg_replace('#((?:\n|^)[ ]*-)\n[ ]+(\w|[\'"])#', '\1 \2', $yaml);

    return trim($yaml);
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    return Yaml::decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Determine if string is valid YAML.
   *
   * @param string $yaml
   *   A YAML string.
   *
   * @return bool
   *   TRUE if string is valid YAML.
   */
  public static function isValid($yaml) {
    return self::validate($yaml) ? FALSE : TRUE;
  }

  /**
   * Validate YAML string.
   *
   * @param string $yaml
   *   A YAML string.
   *
   * @return null|string
   *   NULL if the YAML string contains no errors, else the parsing exception
   *   message is returned.
   */
  public static function validate($yaml) {
    try {
      Yaml::decode($yaml);
      return NULL;
    }
    catch (\Exception $exception) {
      return $exception->getMessage();
    }
  }

  /**
   * Tidy export YAML includes tweaking array layout and multiline strings.
   *
   * @param string $yaml
   *   The output generated from \Drupal\Core\Serialization\Yaml::encode.
   *
   * @return string
   *   The encoded data.
   *
   * @see https://www.drupal.org/project/drupal/issues/2844452
   * @see \Drupal\Component\Serialization\YamlSymfony::encode
   */
  public static function tidy($yaml) {
    // Converting carriage returns (\r\n) to basic returns (\n).
    // [Yaml] don't split lines on carriage returns when dumping #25864.
    // @see https://github.com/symfony/symfony/pull/25864
    $yaml = str_replace('\r\n', '\n', $yaml);
    $data = self::decode($yaml);
    return self::encode($data);
  }

}
