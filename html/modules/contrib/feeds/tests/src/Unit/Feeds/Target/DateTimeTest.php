<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\feeds\Feeds\Target\DateTime;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\DateTime
 * @group feeds
 */
class DateTimeTest extends FieldTargetWithContainerTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The target definition.
   *
   * @var \Drupal\feeds\TargetDefinitionInterface
   */
  protected $targetDefinition;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->feedType = $this->getMock('Drupal\feeds\FeedTypeInterface');
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateTime', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'time']));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return DateTime::class;
  }

  /**
   * Tests preparing a value that succeeds.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateTime', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'date']));

    $configuration = [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
    ];
    $target = new DateTime($configuration, 'datetime', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 1411606273];
    $method(0, $values);
    $this->assertSame(date(DATETIME_DATE_STORAGE_FORMAT, 1411606273), $values['value']);
  }

  /**
   * Tests preparing a value that fails.
   *
   * @covers ::prepareValue
   */
  public function testWithErrors() {
    $configuration = [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
    ];
    $target = new DateTime($configuration, 'datetime', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => '2000-05-32'];
    $method(0, $values);
    $this->assertSame('', $values['value']);
  }

  /**
   * Tests parsing a year value.
   *
   * @covers ::prepareValue
   */
  public function testYearValue() {
    $configuration = [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
    ];
    $target = new DateTime($configuration, 'datetime', []);
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => '2000'];
    $method(0, $values);
    $this->assertSame('2000-01-01T00:00:00', $values['value']);
  }

  /**
   * Test the timezone configuration.
   */
  public function testGetTimezoneConfiguration() {
    // Timezone setting for default timezone.
    $container = new ContainerBuilder();
    $config = ['system.date' => ['timezone.default' => 'UTC']];
    $container->set('config.factory', $this->getConfigFactoryStub($config));
    \Drupal::setContainer($container);

    $method = $this->getMethod('Drupal\feeds\Feeds\Target\DateTime', 'prepareTarget')->getClosure();
    $this->targetDefinition = $method($this->getMockFieldDefinition(['datetime_type' => 'date']));

    // Test timezone options with one of the timezones.
    $configuration = [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
      'timezone' => 'Europe/Helsinki',
    ];
    $target = new DateTime($configuration, 'datetime', []);
    $method = $this->getProtectedClosure($target, 'getTimezoneConfiguration');

    $this->assertSame('Europe/Helsinki', $method());

    // Test timezone options with site default option.
    $configuration = [
      'feed_type' => $this->feedType,
      'target_definition' => $this->targetDefinition,
      'timezone' => '__SITE__',
    ];
    $target = new DateTime($configuration, 'datetime', []);
    $method = $this->getProtectedClosure($target, 'getTimezoneConfiguration');

    $this->assertSame('UTC', $method());
  }

}
