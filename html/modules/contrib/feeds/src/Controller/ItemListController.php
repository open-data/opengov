<?php

namespace Drupal\feeds\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lists the feed items belonging to a feed.
 */
class ItemListController extends ControllerBase {

  /**
   * Provides a object for obtaining system time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Provides a service to handle various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The object for obtaining system time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The services of date.
   */
  public function __construct(TimeInterface $time, DateFormatterInterface $date_formatter) {
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * Lists the feed items belonging to a feed.
   */
  public function listItems(FeedInterface $feeds_feed, Request $request) {
    $processor = $feeds_feed->getType()->getProcessor();

    $header = [
      'id' => $this->t('ID'),
      'title' => $this->t('Label'),
      'imported' => $this->t('Imported'),
      'guid' => [
        'data' => $this->t('GUID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'url' => [
        'data' => $this->t('URL'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
      '#empty' => $this->t('There are no items yet.'),
    ];

    // @todo Allow processors to create their own entity listings.
    if (!$processor instanceof EntityProcessorInterface) {
      return $build;
    }

    $entity_ids = $this->entityTypeManager()->getStorage($processor->entityType())->getQuery()
      ->condition('feeds_item.target_id', $feeds_feed->id())
      ->pager(50)
      ->sort('feeds_item.imported', 'DESC')
      ->execute();

    $storage = $this->entityTypeManager()->getStorage($processor->entityType());
    foreach ($storage->loadMultiple($entity_ids) as $entity) {
      $ago = $this->dateFormatter->formatInterval($this->time->getRequestTime() - $entity->get('feeds_item')->imported);
      $row = [];

      // Entity ID.
      $row[] = $entity->id();

      // Entity link.
      try {
        $row[] = [
          'data' => $entity->toLink(Unicode::truncate($entity->label(), 75, TRUE, TRUE)),
          'title' => $entity->label(),
        ];
      }
      catch (UndefinedLinkTemplateException $e) {
        $row[] = $entity->label();
      }

      // Imported ago.
      $row[] = $this->t('@time ago', ['@time' => $ago]);
      // Item GUID.
      $row[] = [
        'data' => Html::escape(Unicode::truncate($entity->get('feeds_item')->guid, 30, FALSE, TRUE)),
        'title' => $entity->get('feeds_item')->guid,
      ];
      // Item URL.
      $row[] = [
        'data' => Html::escape(Unicode::truncate($entity->get('feeds_item')->url, 30, FALSE, TRUE)),
        'title' => $entity->get('feeds_item')->url,
      ];

      $build['table']['#rows'][] = $row;
    }

    $build['pager'] = ['#type' => 'pager'];
    $build['#title'] = $this->t('%title items', ['%title' => $feeds_feed->label()]);

    return $build;
  }

}
