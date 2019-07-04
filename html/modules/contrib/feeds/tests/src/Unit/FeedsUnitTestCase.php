<?php

namespace Drupal\Tests\feeds\Unit {

  use Drupal\Core\StreamWrapper\StreamWrapperManager;
  use Drupal\Tests\feeds\Traits\FeedsMockingTrait;
  use Drupal\Tests\feeds\Traits\FeedsReflectionTrait;
  use Drupal\Tests\UnitTestCase;
  use org\bovigo\vfs\vfsStream;

  /**
   * Base class for Feeds unit tests.
   */
  abstract class FeedsUnitTestCase extends UnitTestCase {

    use FeedsMockingTrait;
    use FeedsReflectionTrait;

    /**
     * {@inheritdoc}
     */
    public function setUp() {
      parent::setUp();

      $this->defineConstants();
      vfsStream::setup('feeds');
    }

    /**
     * Returns the absolute directory path of the Feeds module.
     *
     * @return string
     *   The absolute path to the Feeds module.
     */
    protected function absolutePath() {
      return dirname(dirname(dirname(__DIR__)));
    }

    /**
     * Returns the absolute directory path of the resources folder.
     *
     * @return string
     *   The absolute path to the resources folder.
     */
    protected function resourcesPath() {
      return $this->absolutePath() . '/tests/resources';
    }

    /**
     * Returns a mock stream wrapper manager.
     *
     * @return \Drupal\Core\StreamWrapper\StreamWrapperManager
     *   A mocked stream wrapper manager.
     */
    protected function getMockStreamWrapperManager() {
      $mock = $this->getMock(StreamWrapperManager::class, [], [], '', FALSE);

      $wrappers = [
        'vfs' => 'VFS',
        'public' => 'Public',
      ];

      $mock->expects($this->any())
        ->method('getDescriptions')
        ->will($this->returnValue($wrappers));

      $mock->expects($this->any())
        ->method('getWrappers')
        ->will($this->returnValue($wrappers));

      return $mock;
    }

    /**
     * Defines stub constants.
     */
    protected function defineConstants() {
      if (!defined('DATETIME_STORAGE_TIMEZONE')) {
        define('DATETIME_STORAGE_TIMEZONE', 'UTC');
      }
      if (!defined('DATETIME_DATETIME_STORAGE_FORMAT')) {
        define('DATETIME_DATETIME_STORAGE_FORMAT', 'Y-m-d\TH:i:s');
      }
      if (!defined('DATETIME_DATE_STORAGE_FORMAT')) {
        define('DATETIME_DATE_STORAGE_FORMAT', 'Y-m-d');
      }

      if (!defined('FILE_MODIFY_PERMISSIONS')) {
        define('FILE_MODIFY_PERMISSIONS', 2);
      }
      if (!defined('FILE_CREATE_DIRECTORY')) {
        define('FILE_CREATE_DIRECTORY', 1);
      }
      if (!defined('FILE_EXISTS_RENAME')) {
        define('FILE_EXISTS_RENAME', 0);
      }
      if (!defined('FILE_EXISTS_REPLACE')) {
        define('FILE_EXISTS_REPLACE', 1);
      }
      if (!defined('FILE_EXISTS_ERROR')) {
        define('FILE_EXISTS_ERROR', 2);
      }
      if (!defined('FILE_STATUS_PERMANENT')) {
        define('FILE_STATUS_PERMANENT', 1);
      }
    }

  }
}

namespace {

  use Drupal\Core\Session\AccountInterface;

  if (!function_exists('filter_formats')) {

    /**
     * Stub for filter_formats() function.
     */
    function filter_formats(AccountInterface $account) {
      return ['test_format' => new FeedsFilterStub('Test format')];
    }

  }

  if (!function_exists('file_stream_wrapper_uri_normalize')) {

    /**
     * Stub for file_stream_wrapper_uri_normalize() function.
     */
    function file_stream_wrapper_uri_normalize($dir) {
      return $dir;
    }

  }

  if (!function_exists('file_prepare_directory')) {

    /**
     * Stub for file_prepare_directory() function.
     */
    function file_prepare_directory(&$directory) {
      return mkdir($directory);
    }

  }

  if (!function_exists('drupal_get_user_timezone')) {

    /**
     * Stub for drupal_get_user_timezone() function.
     */
    function drupal_get_user_timezone() {
      return 'UTC';
    }

  }

  if (!function_exists('batch_set')) {

    /**
     * Stub for batch_set() function.
     */
    function batch_set() {
    }

  }


  if (!function_exists('_format_date_callback')) {

    /**
     * Stub for _format_date_callback() function.
     */
    function _format_date_callback(array $matches = NULL, $new_langcode = NULL) {
      // We cache translations to avoid redundant and rather costly calls to
      // t().
      static $cache, $langcode;

      if (!isset($matches)) {
        $langcode = $new_langcode;
        return;
      }

      $code = $matches[1];
      $string = $matches[2];

      if (!isset($cache[$langcode][$code][$string])) {
        $options = [
          'langcode' => $langcode,
        ];

        if ($code == 'F') {
          $options['context'] = 'Long month name';
        }

        if ($code == '') {
          $cache[$langcode][$code][$string] = $string;
        }
        else {
          $cache[$langcode][$code][$string] = t($string, [], $options);
        }
      }
      return $cache[$langcode][$code][$string];
    }

  }

  /**
   * Stub for a filter format entity.
   */
  class FeedsFilterStub {

    /**
     * Constructs a new FeedsFilterStub.
     *
     * @param string $label
     *   The filter's label.
     */
    public function __construct($label) {
      $this->label = $label;
    }

    /**
     * Returns the filter's label.
     *
     * @return string
     *   The label of this filter.
     */
    public function label() {
      return $this->label;
    }

  }
}
