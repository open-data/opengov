<?php

/**
 * @file
 * Contains database additions to drupal-9.4.0.bare.standard.php.gz.
 *
 * This fixture enables the honeypot module by setting system configuration
 * values in the {config} and {key_value} tables, then adds the Honeypot
 * tables to the database using the schema from Honeypot version 2.1.3.
 *
 * This fixture is intended for use in testing honeypot_update_8103().
 *
 * @see https://www.drupal.org/node/3211202.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Create the Honeypot tables. This is from Honeypot version 2.1.3.
$connection->schema()->createTable('honeypot_user', [
  'description' => 'Table that stores failed attempts to submit a form.',
  'fields' => [
    'id' => [
      'type' => 'serial',
      'not null' => TRUE,
      'description' => 'Unique record ID.',
    ],
    'uid' => [
      'description' => 'Foreign key to {users}.uid; uniquely identifies a Drupal user to whom this ACL data applies.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
    'hostname' => [
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'description' => 'Hostname of user that that triggered honeypot.',
    ],
    'timestamp' => [
      'description' => 'Date/time when the form submission failed, as Unix timestamp.',
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ],
  ],
  'primary key' => ['id'],
  'indexes' => [
    'uid' => ['uid'],
    'timestamp' => ['timestamp'],
  ],
]);

// Set the honeypot DB schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'honeypot',
    'value' => 'i:8102;',
  ])
  ->execute();

// Update core.extension to enable honeypot.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['honeypot'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
