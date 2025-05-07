<?php

namespace Drupal\Tests\honeypot\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding a primary key to the {honeypot_user} table.
 *
 * @group honeypot
 * @group legacy
 */
class PrimaryKeyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $d9_specific_dump = DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.3.0.bare.standard.php.gz';
    $d10_specific_dump = DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz';

    // Can't use the same dump in D9 and D10.
    if (file_exists($d9_specific_dump)) {
      $core_dump = $d9_specific_dump;
    }
    else {
      $core_dump = $d10_specific_dump;
    }

    // Use core fixture and Honeypot-specific fixture.
    $this->databaseDumpFiles = [
      $core_dump,
      __DIR__ . '/../../../fixtures/update/drupal-8.honeypot-add-primary-key-2943526.php',
    ];
  }

  /**
   * Tests update hook honeypot_update_8102().
   *
   * @see honeypot_update_8102()
   */
  public function testUpdate(): void {
    // Fixture is built for schema 8101.
    $this->assertSame(8101, \Drupal::keyValue('system.schema')->get('honeypot'));

    $schema = \Drupal::database()->schema();

    // Check that there is no primary key before the update.
    $this->assertFalse($schema->dropPrimaryKey('honeypot_user'));
    $this->assertFalse($schema->fieldExists('honeypot_user', 'id'));

    // Run updates.
    $this->runUpdates();

    // Check that the {honeypot_user} table now contains the 'id' column
    // with the expected schema definition after the update.
    $this->assertTrue($schema->fieldExists('honeypot_user', 'id'));

    $install_schema = honeypot_schema();
    $column_schema = $install_schema['honeypot_user']['fields']['id'];
    $primary_key = $install_schema['honeypot_user']['primary key'];

    // See if the 'id' column has the expected schema definition.
    $this->assertEquals('serial', $column_schema['type']);
    $this->assertTrue($column_schema['not null']);
    $this->assertEquals('Unique record ID.', $column_schema['description']);

    // Verify the primary key now exists.
    $this->assertEquals(['id'], $primary_key);
  }

}
