<?php

namespace Drupal\Tests\memcache\Kernel;

use Drupal\KernelTests\Core\Cache\GenericCacheBackendUnitTestBase;
use Drupal\memcache\MemcacheBackendFactory;

/**
 * Tests the MemcacheBackend.
 *
 * @group memcache
 */
class MemcacheBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'memcache'];

  /**
   * Creates a new instance of DatabaseBackend.
   *
   * @return \Drupal\memcache\MemcacheBackend
   *   A new MemcacheBackend object.
   */
  protected function createCacheBackend($bin) {
    $factory = new MemcacheBackendFactory($this->container->get('memcache.factory'), $this->container->get('cache_tags.invalidator.checksum'));
    return $factory->get($bin);
  }

}
