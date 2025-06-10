<?php

namespace Drupal\Tests\honeypot\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding a 'hostname' column to the {honeypot_user} table.
 *
 * @group honeypot
 * @group legacy
 */
class HostnameColumnUpdateTest extends UpdatePathTestBase {

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
      __DIR__ . '/../../../fixtures/update/drupal-8.honeypot-add-hostname-column-3121331.php',
    ];
  }

  /**
   * Tests update hooks honeypot_update_8100() and honeypot_update_8101().
   *
   * @see honeypot_update_8100()
   * @see honeypot_update_8101()
   */
  public function testUpdate(): void {
    // Fixture is built for schema 8000.
    $this->assertSame(8000, \Drupal::keyValue('system.schema')->get('honeypot'));

    // Note: Its VERY important to use \Drupal::database() here to get the
    // database connection. Other methods like $this->getDatabaseConnection()
    // will give you the WRONG connection.
    $schema = \Drupal::database()->schema();

    // Check that the {honeypot_user} table does not contain the 'hostname'
    // column before the update.
    $exists = $schema->tableExists('honeypot_user');
    $this->assertTrue($exists);
    $exists = $schema->fieldExists('honeypot_user', 'uid');
    $this->assertTrue($exists);
    $exists = $schema->fieldExists('honeypot_user', 'timestamp');
    $this->assertTrue($exists);
    $exists = $schema->fieldExists('honeypot_user', 'hostname');
    $this->assertFalse($exists);

    // Run updates.
    $this->runUpdates();

    // Check that the {honeypot_user} table now contains the 'hostname' column
    // with the expected schema definition after the update.
    $exists = $schema->fieldExists('honeypot_user', 'hostname');
    $this->assertTrue($exists);

    $install_schema = honeypot_schema();
    $column_schema = $install_schema['honeypot_user']['fields']['hostname'];

    $this->assertEquals('varchar', $column_schema['type']);
    $this->assertEquals('128', $column_schema['length']);
    $this->assertTrue($column_schema['not null']);
    $this->assertEquals('Hostname of user that that triggered honeypot.', $column_schema['description']);
  }

}
