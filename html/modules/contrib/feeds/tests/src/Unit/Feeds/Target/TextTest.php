<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\feeds\Feeds\Target\Text;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Text
 * @group feeds
 */
class TextTest extends FieldTargetTestBase {

  /**
   * The FeedsTarget plugin being tested.
   *
   * @var \Drupal\feeds\Feeds\Target\Text
   */
  protected $target;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $method = $this->getMethod('Drupal\feeds\Feeds\Target\Text', 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->getMock('Drupal\feeds\FeedTypeInterface'),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];
    $this->target = new Text($configuration, 'text', [], $this->getMock('Drupal\Core\Session\AccountInterface'));
    $this->target->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Text::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getProtectedClosure($this->target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame('longstring', $values['value']);
    $this->assertSame('plain_text', $values['format']);
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $form_state = new FormState();
    $form = $this->target->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 1);
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummary() {
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->with(['status' => '1', 'format' => 'plain_text'])
      ->will($this->onConsecutiveCalls([new \FeedsFilterStub('Test filter')], []));

    $manager = $this->getMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $manager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValue($storage));

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $manager);
    \Drupal::setContainer($container);

    $this->assertSame('Format: <em class="placeholder">Test filter</em>', (string) $this->target->getSummary());
    $this->assertEquals('', (string) $this->target->getSummary());
  }

}
