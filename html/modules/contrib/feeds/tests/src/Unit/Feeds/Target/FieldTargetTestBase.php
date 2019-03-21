<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\feeds\FieldTargetDefinition;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Base class for testing feeds field targets.
 */
abstract class FieldTargetTestBase extends FeedsUnitTestCase {

  /**
   * Returns the target class.
   *
   * @return string
   *   Returns the full class name of the target to test.
   */
  abstract protected function getTargetClass();

  /**
   * @covers ::prepareTarget
   */
  public function testPrepareTarget() {
    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    $this->assertInstanceof(FieldTargetDefinition::class, $method($this->getMockFieldDefinition()));
  }

}
