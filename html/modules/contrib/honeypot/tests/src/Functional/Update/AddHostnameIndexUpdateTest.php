<?php

namespace Drupal\Tests\honeypot\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding a primary key to the {honeypot_user} table.
 *
 * @group honeypot
 * @group legacy
 */
class AddHostnameIndexUpdateTest extends UpdatePathTestBase {

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
      __DIR__ . '/../../../fixtures/update/drupal-8.honeypot-add-hostname-index-3211202.php',
    ];
  }

  /**
   * Tests update hook honeypot_update_8103().
   *
   * @see honeypot_update_8103()
   */
  public function testUpdate(): void {
    // Fixture is built for schema 8102.
    $this->assertSame(8102, \Drupal::keyValue('system.schema')->get('honeypot'));

    $schema = \Drupal::database()->schema();

    // There should be no index on the hostname column before the update.
    $this->assertFalse($schema->indexExists('honeypot_user', 'hostname'));

    // Run updates.
    $this->runUpdates();

    // The hostname column should now be indexed.
    $this->assertTrue($schema->indexExists('honeypot_user', 'hostname'));
  }

}
