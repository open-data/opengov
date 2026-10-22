<?php

// phpcs:ignoreFile SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Drupal\Tests\search_api_solr\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\search_api_solr\SearchApiSolrException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextToken;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api_solr\Controller\AbstractSolrEntityListBuilder;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Drupal\search_api_solr\Plugin\search_api\data_type\value\DateRangeValue;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginManager;
use Drupal\Tests\search_api_solr\Traits\InvokeMethodTrait;
use Solarium\Core\Query\Helper;
use Solarium\QueryType\Update\Query\Document;

/**
 * Tests functionality of the backend.
 *
 * @coversDefaultClass \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend
 *
 * @group search_api_solr
 */
class SearchApiBackendUnitTest extends Drupal10CompatibilityUnitTestCase {

  use InvokeMethodTrait;

  /**
   * Provides the Solr entities list builder.
   *
   * @var \Drupal\search_api_solr\Controller\AbstractSolrEntityListBuilder
   */
  protected $listBuilder;

  /**
   * The entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The query helper object.
   *
   * @var \Solarium\Core\Query\Helper
   */
  protected $queryHelper;

  /**
   * Apache Solr backend.
   *
   * @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend
   */
  protected $backend;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->listBuilder = $this->createMock(AbstractSolrEntityListBuilder::class);
    $this->listBuilder->method('getAllNotRecommendedEntities')->willReturn([]);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getListBuilder')->willReturn($this->listBuilder);

    // This helper is actually used.
    $this->queryHelper = new Helper();

    $connector_manager = $this->createMock(SolrConnectorPluginManager::class);
    $connector_manager->method('createInstance')->with(NULL, [])->willThrowException(new SearchApiSolrException('no connector'));

    $this->backend = new SearchApiSolrBackend([], NULL, [],
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $this->prophesize(Config::class)->reveal(),
      $this->prophesize(LanguageManagerInterface::class)->reveal(),
      $connector_manager,
      $this->prophesize(FieldsHelperInterface::class)->reveal(),
      $this->prophesize(DataTypeHelperInterface::class)->reveal(),
      $this->queryHelper,
      $this->entityTypeManager,
      $this->prophesize(EventDispatcher::class)->reveal(),
      $this->prophesize(TimeInterface::class)->reveal(),
      $this->prophesize(StateInterface::class)->reveal(),
      $this->prophesize(MessengerInterface::class)->reveal(),
      $this->prophesize(LockBackendInterface::class)->reveal(),
      $this->prophesize(ModuleExtensionList::class)->reveal(),
      $this->createMock(ContainerInterface::class)
    );
  }

  /**
   * @covers       ::addIndexField
   *
   * @dataProvider addIndexFieldDataProvider
   *
   * @param mixed $input
   *   Field value.
   *
   * @param string $type
   *   Field type.
   *
   * @param mixed $expected
   *   Expected result.
   */
  public function testIndexField($input, $type, $expected) {
    $field = 'testField';
    $document = $this->createMock(Document::class);

    if (NULL !== $expected) {
      if (is_array($expected)) {
        $document->expects($this->once())
          ->method('addField')
          ->with($field, $expected[0], $expected[1]);
      }
      else {
        $document->expects($this->once())
          ->method('addField')
          ->with($field, $expected);
      }
    }
    else {
      $document->expects($this->never())
        ->method('addField');
    }

    $boost_terms = [];
    $args = [
      $document,
      $field,
      [$input],
      $type,
      &$boost_terms,
    ];

    // addIndexField() should convert the $input according to $type and call
    // Document::addField() with the correctly converted $input.
    $this->invokeMethod(
      $this->backend,
      'addIndexField',
      $args,
      []
    );
  }

  /**
   * @covers       ::addIndexField
   *
   * @dataProvider addIndexEmptyFieldDataProvider
   *
   * @param mixed $input
   *   Field value.
   *
   * @param string $type
   *   Field type.
   *
   * @param mixed $expected
   *   Expected result.
   */
  public function testIndexEmptyField($input, $type, $expected) {
    $field = 'testField';
    $document = $this->createMock(Document::class);

    $document->expects($this->once())
      ->method('addField')
      ->with($field, $expected);

    $boost_terms = [];
    $args = [
      $document,
      $field,
      [$input],
      $type,
      &$boost_terms,
    ];

    $this->backend->setConfiguration(['index_empty_text_fields' => TRUE] + $this->backend->getConfiguration());

    // addIndexField() should convert the $input according to $type and call
    // Document::addField() with the correctly converted $input.
    $this->invokeMethod(
      $this->backend,
      'addIndexField',
      $args,
      []
    );
  }

  /**
   * Provides test format date.
   */
  public function testFormatDate() {
    $this->assertFalse($this->backend->formatDate('asdf'));
    $this->assertEquals('1992-08-27T00:00:00Z', $this->backend->formatDate('1992-08-27'));
  }

  /**
   * Data provider for testIndexField method.
   */
  public static function addIndexFieldDataProvider() {
    return [
      // addIndexField() should be called.
      ['0', 'boolean', 'false'],
      ['1', 'boolean', 'true'],
      [0, 'boolean', 'false'],
      [1, 'boolean', 'true'],
      [FALSE, 'boolean', 'false'],
      [TRUE, 'boolean', 'true'],
      ['2016-05-25T14:00:00+10', 'date', '2016-05-25T04:00:00Z'],
      ['1465819200', 'date', '2016-06-13T12:00:00Z'],
      [
        new DateRangeValue('2016-05-25T14:00:00+10', '2017-05-25T14:00:00+10'),
        'solr_date_range',
        '[2016-05-25T04:00:00Z TO 2017-05-25T04:00:00Z]',
      ],
      [-1, 'integer', -1],
      [0, 'integer', 0],
      [1, 'integer', 1],
      [-1.0, 'decimal', -1.0],
      [0.0, 'decimal', 0.0],
      [1.3, 'decimal', 1.3],
      ['foo', 'string', 'foo'],
      [new TextValue('foo bar'), 'text', 'foo bar'],
      [(new TextValue(''))->setTokens([new TextToken('bar')]), 'text', 'bar'],
      // addIndexField() should not be called.
      [NULL, 'boolean', NULL],
      [NULL, 'date', NULL],
      [NULL, 'solr_date_range', NULL],
      [NULL, 'integer', NULL],
      [NULL, 'decimal', NULL],
      [NULL, 'string', NULL],
      ['', 'string', NULL],
      [new TextValue(''), 'text', NULL],
      [(new TextValue(''))->setTokens([new TextToken('')]), 'text', NULL],
    ];
  }

  /**
   * Data provider for testIndexEmptyField method.
   */
  public static function addIndexEmptyFieldDataProvider() {
    return [
      [
        new TextValue(''),
        'text',
        SolrBackendInterface::EMPTY_TEXT_FIELD_DUMMY_VALUE,
      ],
      [
        (new TextValue(''))->setTokens([new TextToken('')]),
        'text',
        SolrBackendInterface::EMPTY_TEXT_FIELD_DUMMY_VALUE,
      ],
      [
        NULL,
        'text',
        SolrBackendInterface::EMPTY_TEXT_FIELD_DUMMY_VALUE,
      ],
    ];
  }

}
