<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests comments can be accessed on a translated node.
 *
 * @group comment
 */
class CommentOnTranslatedNodeTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'content_translation',
    'language',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with access to view comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Node for commenting.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create article content type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Add comment field to article content type.
    $this->addDefaultCommentField('node', 'article');

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('hi')->save();

    // Create test user.
    $this->webUser = $this->drupalCreateUser([
      'access comments',
    ]);

    // Create a test node and add translation.
    $this->node = $this->createNode([
      'title' => 'Test node en',
      'type' => 'article',
    ]);
    $this->node->addTranslation('hi', ['title' => 'Test node hi']);
    $this->node->save();

    // Create a comment on the node.
    $comment = Comment::create([
      'entity_type' => 'node',
      'subject' => 'Comment on node',
      'entity_id' => $this->node->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'uid' => $this->node->getOwnerId(),
      'status' => CommentInterface::PUBLISHED,
    ]);
    $comment->save();
  }

  /**
   * Tests comments can be accessed on a translated node.
   */
  public function testCommentOnTranslatedNode() {
    $assertSession = $this->assertSession();

    // Verify that user with 'access comments' permission can view the comment.
    $this->drupalLogin($this->webUser);

    $node_url = 'node/' . $this->node->id();
    $this->drupalGet($node_url);
    $assertSession->pageTextContains('Comment on node');

    $node_url = 'hi/node/' . $this->node->id();
    $this->drupalGet($node_url);
    $assertSession->pageTextContains('Comment on node');
  }

}
