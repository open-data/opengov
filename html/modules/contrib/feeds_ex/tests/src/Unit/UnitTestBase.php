<?php

namespace Drupal\Tests\feeds_ex\Unit;

use ReflectionMethod;
use Drupal\Tests\feeds\Unit\FeedsUnitTestCase;

/**
 * Base class for units tests.
 */
abstract class UnitTestBase extends FeedsUnitTestCase {

  /**
   * The module directory.
   *
   * @var string
   */
  protected $moduleDir;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->moduleDir = dirname(dirname(dirname(dirname(__FILE__))));

    parent::setUp();
  }

  /**
   * Calls a private or protected method on an object.
   *
   * @param object $object
   *   The object to invoke a method on.
   * @param string $method
   *   The name of the method.
   * @param array $arguments
   *   (optional) The arguments to provide to the method. Defaults to an empty
   *   array.
   *
   * @return mixed
   *   Whatever the method returns.
   */
  protected function invokeMethod($object, $method, array $arguments = []) {
    $reflector = new ReflectionMethod($object, $method);
    $reflector->setAccessible(TRUE);
    return $reflector->invokeArgs($object, $arguments);
  }

  /**
   * Asserts that the empty message is correct.
   *
   * @param array $messages
   *   The list of error messages.
   */
  protected function assertEmptyFeedMessage(array $messages) {
    $this->assertSame(1, count($messages), strtr('There is one message (actual: @actual).', array(
      '@actual' => count($messages),
    )));
    $this->assertSame((string) $messages[0]['message'], 'The feed is empty.', 'Message text is correct.');
    $this->assertSame($messages[0]['type'], 'warning', 'Message type is warning.');
    $this->assertFalse($messages[0]['repeat'], 'Repeat is set to false.');
  }

}
