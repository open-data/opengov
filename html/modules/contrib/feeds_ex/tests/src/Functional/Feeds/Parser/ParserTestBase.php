<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

use Drupal\Tests\feeds_ex\Functional\FeedsExBrowserTestBase;

/**
 * Base class for parser functional tests.
 */
abstract class ParserTestBase extends FeedsExBrowserTestBase {

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = '';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a feed type.
    $this->feedType = $this->createFeedType([
      'parser' => $this->parserId,
    ]);
  }

}
