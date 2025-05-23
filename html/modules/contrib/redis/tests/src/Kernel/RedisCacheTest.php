<?php

namespace Drupal\Tests\redis\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\Core\Cache\GenericCacheBackendUnitTestBase;
use Symfony\Component\DependencyInjection\Reference;
use Drupal\Tests\redis\Traits\RedisTestInterfaceTrait;

/**
 * Tests Redis cache backend using GenericCacheBackendUnitTestBase.
 *
 * @group redis
 */
class RedisCacheTest extends GenericCacheBackendUnitTestBase {

  use RedisTestInterfaceTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'redis'];

  public function register(ContainerBuilder $container) {
    self::setUpSettings();
    parent::register($container);
    // Replace the default checksum service with the redis implementation.
    if ($container->has('redis.factory')) {
      $container->register('cache_tags.invalidator.checksum', 'Drupal\redis\Cache\RedisCacheTagsChecksum')
        ->addArgument(new Reference('redis.factory'))
        ->addTag('cache_tags_invalidator');
    }
  }

  /**
   * Creates a new instance of PhpRedis cache backend.
   *
   * @return \Drupal\redis\Cache\PhpRedis
   *   A new PhpRedis cache backend.
   */
  protected function createCacheBackend($bin) {
    $cache = \Drupal::service('cache.backend.redis')->get($bin);
    $cache->setMinTtl(10);
    return $cache;
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   *
   * @group legacy
   */
  public function testInvalidateAllOptimized(): void {
    $this->setSetting('redis_invalidate_all_as_delete', TRUE);
    $backend_a = $this->getCacheBackend();
    $backend_b = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend_a->set('test1', 1, Cache::PERMANENT);
    $backend_a->set('test2', 3, time() + 1000);
    $backend_b->set('test3', 4, Cache::PERMANENT);

    $backend_a->invalidateAll();

    $this->assertFalse($backend_a->get('test1'), 'First key has been invalidated.');
    $this->assertFalse($backend_a->get('test2'), 'Second key has been invalidated.');
    $this->assertNotEmpty($backend_b->get('test3'), 'Item in other bin is preserved.');

    // Keys can also no longer be retrieved when allowing invalid caches to be
    // returned.
    $this->assertEmpty($backend_a->get('test1', TRUE), 'First key has been deleted.');
    $this->assertEmpty($backend_a->get('test2', TRUE), 'Second key has been deleted.');
  }

}
