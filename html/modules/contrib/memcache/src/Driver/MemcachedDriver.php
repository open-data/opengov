<?php

namespace Drupal\memcache\Driver;

/**
 * Class MemcachedDriver.
 */
class MemcachedDriver extends DriverBase {

  /**
   * {@inheritdoc}
   */
  public function set($key, $value, $exp = 0, $flag = FALSE) {
    $collect_stats = $this->statsInit();

    $full_key = $this->key($key);
    $result = $this->memcache->set($full_key, $value, $exp);

    if ($collect_stats) {
      $this->statsWrite('set', 'cache', [$full_key => (int) $result]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function add($key, $value, $expire = 0) {
    $collect_stats = $this->statsInit();

    $full_key = $this->key($key);
    $result = $this->memcache->add($full_key, $value, $expire);

    if ($collect_stats) {
      $this->statsWrite('add', 'cache', [$full_key => (int) $result]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMulti(array $keys) {
    $collect_stats = $this->statsInit();
    $multi_stats   = [];

    $full_keys = [];

    foreach ($keys as $key => $cid) {
      $full_key = $this->key($cid);
      $full_keys[$cid] = $full_key;

      if ($collect_stats) {
        $multi_stats[$full_key] = FALSE;
      }
    }

    if (PHP_MAJOR_VERSION === 7) {
      $results = $this->memcache->getMulti($full_keys, \Memcached::GET_PRESERVE_ORDER);
    }
    else {
      $cas_tokens = NULL;
      $results = $this->memcache->getMulti($full_keys, $cas_tokens, \Memcached::GET_PRESERVE_ORDER);
    }

    // If $results is FALSE, convert it to an empty array.
    if (!$results) {
      $results = [];
    }

    if ($collect_stats) {
      foreach ($multi_stats as $key => $value) {
        $multi_stats[$key] = isset($results[$key]) ? TRUE : FALSE;
      }
    }

    // Convert the full keys back to the cid.
    $cid_results = [];
    $cid_lookup = array_flip($full_keys);

    foreach (array_filter($results) as $key => $value) {
      $cid_results[$cid_lookup[$key]] = $value;
    }

    if ($collect_stats) {
      $this->statsWrite('getMulti', 'cache', $multi_stats);
    }

    return $cid_results;
  }

}
