<?php

namespace Drupal\redis\Queue;

use Drupal\Core\Queue\QueueFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\redis\ClientFactory;

// BC for Drupal core 10.1 and earlier.
if (!interface_exists(QueueFactoryInterface::class)) {
  interface BCQueueFactoryInterface {
  }
  class_alias(BCQueueFactoryInterface::class, QueueFactoryInterface::class);
}

/**
 * Defines the queue factory for the Redis backend.
 */
class QueueRedisFactory implements QueueFactoryInterface {

  /**
   * Queue implementation class namespace prefix.
   */
  const CLASS_NAMESPACE = ClientFactory::REDIS_IMPL_QUEUE;

  /**
   * @var \Drupal\redis\ClientFactory
   */
  protected $clientFactory;

  /**
   * The settings array.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\redis\ClientFactory $client_factory
   *   Client factory for generating the redis connection.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal settings for creating the connection.
   */
  public function __construct(ClientFactory $client_factory, Settings $settings) {
    $this->clientFactory = $client_factory;
    $this->settings = $settings;
  }

  /**
   * Constructs a new queue object for a given name.
   *
   * @param string $name
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\redis\Queue\PhpRedis|\Drupal\redis\Queue\Predis
   *   A key/value store implementation for the given $collection.
   */
  public function get($name) {
    $settings = $this->settings->get('redis_queue_' . $name, ['reserve_timeout' => NULL]);
    $class_name = $this->clientFactory->getClass(static::CLASS_NAMESPACE);
    return new $class_name($name, $settings, $this->clientFactory->getClient());
  }

}
