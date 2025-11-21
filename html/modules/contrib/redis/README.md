# Drupal Redis documentation

## Redis clients

This package provides support for three different Redis clients.

  * PhpRedis
  * Predis
  * Relay (See configuration recommendations for in-memory cache)

By default, the first available client will be used in that order, to configure
it explicitly, use

    $settings['redis.connection']['interface'] = 'PhpRedis';

Each supported client has its own README client specific installation and
configuration options.

## Common configuration

See settings.redis.example.php for a quick start and recommended configuration.

Customize the default host and port:

    $settings['redis.connection']['host'] = '127.0.0.1';
    $settings['redis.connection']['port'] = 6379;

Use Redis for all caches:

    $settings['cache']['default'] = 'cache.backend.redis';

Configure usage for a specific bin

    $settings['cache']['bins']['render'] = 'cache.backend.redis';

The example.services.yml from the module will replace the cache tags checksum
service, flood and the lock backends (check the file for the current list).
Either include it directly or copy the desired service definitions into a site
specific services.yml file for more control.

    $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

It is recommended to enable the redis module, to use the report feature, but
the redis.services.yml can also be included explicitly.

    $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

Compressing the data stored in redis can massively reduce the needed storage.

To enable, set the minimal length after which the cached data should be
compressed:

    $settings['redis_compress_length'] = 100;

By default, compression level 1 is used, which provides considerable storage
optimization with minimal CPU overhead, to change:

    $settings['redis_compress_level'] = 6;

Redis can also be used for the container cache bin, the bootstrap container
needs to be configured for that.

    $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        'redis.factory' => [
          'class' => 'Drupal\redis\ClientFactory',
        ],
        'cache.backend.redis' => [
          'class' => 'Drupal\redis\Cache\CacheBackendFactory',
          'arguments' => ['@redis.factory', '@cache_tags_provider.container', '@serialization.phpserialize'],
        ],
        'cache.container' => [
          'class' => '\Drupal\redis\Cache\PhpRedis',
          'factory' => ['@cache.backend.redis', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
          'arguments' => ['@redis.factory'],
        ],
        'serialization.phpserialize' => [
          'class' => 'Drupal\Component\Serialization\PhpSerialize',
        ],
      ],
    ];

## Use persistent connections

This mode needs the following setting:

    $settings['redis.connection']['persistent'] = TRUE;

## Using a specific database

Per default, Redis ships the database "0". All default connections will be use
this one if nothing is specified.

Depending on you OS or OS distribution, you might have numerous database. To
use one in particular, just add to your settings.php file:

    $settings['redis.connection']['base']      = 12;

## Connection to a password protected instance

If you are using a password protected instance, specify the password this way:

    $settings['redis.connection']['password'] = "mypassword";

Depending on the backend, using a wrong auth will behave differently:

- Predis will throw an exception and make Drupal fail during early boostrap.

- PhpRedis will make Redis calls silent and creates some PHP warnings, thus
  Drupal will behave as if it was running with a null cache backend (no cache
  at all).

## Prefixing site cache entries (avoiding sites name collision)

If you need to differentiate multiple sites using the same Redis instance and
database, you will need to specify a prefix for your site cache entries.

Cache prefix configuration attempts to use a unified variable across contrib
backends that support this feature. This variable name is 'cache_prefix'.

This variable is polymorphic, the simplest version is to provide a raw string
that will be the default prefix for all cache bins:

    $settings['cache_prefix'] = 'mysite_';

Alternatively, to provide the same functionality, you can provide the variable
as an array:

    $settings['cache_prefix']['default'] = 'mysite_';

This allows you to provide different prefix depending on the bin name. Common
usage is that each key inside the 'cache_prefix' array is a bin name, the value
the associated prefix. If the value is FALSE, then no prefix is
used for this bin.

The 'default' meta bin name is provided to define the default prefix for non
specified bins. It behaves like the other names, which means that an explicit
FALSE will order the backend not to provide any prefix for any non specified
bin.

Here is a complex sample:

    // Default behavior for all bins, prefix is 'mysite_'.
    $settings['cache_prefix']['default'] = 'mysite_';

    // Set no prefix explicitly for 'cache' and 'cache_bootstrap' bins.
    $settings['cache_prefix']['cache'] = FALSE;
    $settings['cache_prefix']['cache_bootstrap'] = FALSE;

    // Set another prefix for 'cache_menu' bin.
    $settings['cache_prefix']['cache_menu'] = 'menumysite_';

If no prefix is set explicitly set, it will fall back to the behavior that is
used for the APCU prefix, which is reasonably safe but quite long. Setting a
explicit prefix is recommended.

## Redis memory management

Redis is typically configured with a max memory size that it is allowed to use.

What happens when that is full depends on the eviction policy. By default, Redis
will no longer accept new items. which is almost never the desired behavior when
using Redis as a Drupal cache backend, as caches tend to grow continuously.

The other policies are split in two groups and affect either all keys or only
volatile ones, which are defined by having an expiration/TTL set.

The redis cache backend will always set an expiration on all cache items, with
the exception of cache taq invalidations as well as queue and other similar
backend implementations. It is therefore safe and recommended
to use a volatile policy to avoid evicting cache tag invalidations or queue
items.

A good policy for typical scenarios is volatile-lfu, but sites may want to do
their own testing and research on this. Sites with a large amount of items with
shorter TTL or optimized permanent TTLs for certain bins may benefit from using
volatile-ttl, but only if those items aren't also very frequently used.

It is recommend to store data that must be persisted like queue items in a
separate redis instance.

See https://valkey.io/topics/lru-cache/ for a detailed explanation.

## Expiration of cache items

Per default the TTL for permanent items will set to safe-enough value which is
one year; No matter how Redis will be configured default configuration or lazy
admin will inherit from a safe module behavior with zero-conf.

The default TTL can be customized for specific bins.

    // Set max TTL for cached pages to 3 days.
    $settings['redis_perm_ttl_page'] = '3 days';

    // But you can also put a timestamp in there; In this case the
    // value must be a typed integer:
    $settings['redis_perm_ttl_page'] = 2592000; // 30 days.

Time interval string will be parsed using DateInterval::createFromDateString
please refer to its documentation:

    http://www.php.net/manual/en/dateinterval.createfromdatestring.php

By default, the default TTL will always be used over the expiration set for the
item. The specific expiration is instead stored and verified when reading
the cache item.

This is done to respect the expectation that expired cache items can still be
returned when explicitly requested.

It is possible to set a TTL offset as a compromise between supporting the
ability to return expired items for a certain amount of time but also guide
Redis to clear item items that have been expired.

Using this setting and a value of 0, Redis will get the real TTL for a key and
might evict keys by TTL according to its configuration.

    // Expired items can still be explicitly requested for up to
    // one hour.
    $settings['redis_ttl_offset'] = 3600;

    // It is also possible to set the offset to 0, which disables the ability
    // to fetch expired items. This is not recommended.
    $settings['redis_ttl_offset'] = 0;

Note: This behavior is off by default for BC, a default offset might be set in a
future release.

## Cache optimizations

These settings allow to further optimize caching but are not be fully compatible
with the expected behavior of cache backends or have other tradeoffs.

Treat invalidateAll() the same as deleteAll() to avoid two different checks for
each bin.

    $settings['redis_invalidate_all_as_delete'] = TRUE;

Core has deprecated invalidateAll() in
https://www.drupal.org/project/drupal/issues/3498947. This setting will be
removed in the future when Drupal 12.0 is required.

## Additional backends

### Lock Backend

See the provided example.services.yml file on how to override the lock services.

### Queue Backend

This module provides reliable and non-reliable queue implementations. Depending
on which is to be use you need to choose "queue.redis" or "queue.redis_reliable"
as a service name.

When you have configured basic information (host, library, ... - see Quick setup)
add this to your settings.php file:

    # Use for all queues unless otherwise specified for a specific queue.
    $settings['queue_default'] = 'queue.redis';

    # Or if you want to use reliable queue implementation.
    $settings['queue_default'] = 'queue.redis_reliable';

    # Use this to only use Redis for a specific queue (aggregator_feeds in this case).
    $settings['queue_service_aggregator_feeds'] = 'queue.redis';

    # Or if you want to use reliable queue implementation.
    $settings['queue_service_aggregator_feeds'] = 'queue.redis_reliable';

## Testing

The tests respect the following two environment variables to customize the redis
host and used interface.

  * REDIS_HOST
  * REDIS_INTERFACE

These can for example be configured through phpunit.xml

    <env name="REDIS_HOST" value="redis"/>
    <env name="REDIS_INTERFACE" value="Relay"/>
