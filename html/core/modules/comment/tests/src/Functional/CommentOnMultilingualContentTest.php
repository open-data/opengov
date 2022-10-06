<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests comments on multilingual content.
 *
 * @group comment
 */
class CommentOnMultilingualContentTest extends BrowserTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'comment',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add languages.
    ConfigurableLanguage::createFromLangcode('mr')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->resetAll();

    // Create an article content type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => t('Article'),
    ]);
    // Create comment field on article.
    $this->addDefaultCommentField('node', 'article');

    $this->drupalLogin($this->drupalCreateUser([
      'access comments',
      'post comments',
      'create article content',
      'edit own comments',
      'skip comment approval',
      'access content',
    ]));
  }

  /**
   * Posts a comment on a given node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Node to post comment on.
   * @param string $langcode
   *   String language code.
   */
  protected function postCommentOnMultilingualContent(EntityInterface $node, string $langcode): void {
    $options = [
      'language' => ConfigurableLanguage::load($langcode),
    ];

    // Get the comment form.
    $url = Url::fromRoute('comment.reply', [
      'entity_type' => $node->getEntityTypeId(),
      'entity' => $node->id(),
      'field_name' => 'comment',
    ], $options);
    $this->drupalGet($url);

    // Comment data.
    $edit = [];
    $edit['subject[0][value]'] = $langcode . ': ' . $this->randomString();
    $edit['comment_body[0][value]'] = $this->getRandomGenerator()
      ->paragraphs(2);
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests redirections with posting comments on multilingual content.
   */
  public function testCommentOnMultilingualContent(): void {
    // Create multilingual content.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Test title',
    ]);

    $node->addTranslation('mr', ['title' => 'Marathi title'])->save();

    $assert_session = $this->assertSession();
    $language_manager = \Drupal::service('language_manager');

    // Post a comment on default language.
    $this->postCommentOnMultilingualContent($node, $node->language()->getId());
    $assert_session->addressEquals(
      Url::fromRoute('entity.node.canonical',
        ['node' => $node->id()],
        [
          'fragment' => 'comment-1',
          'language' => $language_manager->getLanguage('en'),
        ]
      )
    );

    // Post a comment on Marathi translation.
    $this->postCommentOnMultilingualContent($node, 'mr');
    $assert_session->addressEquals(
      Url::fromRoute('entity.node.canonical',
        ['node' => $node->id()],
        [
          'fragment' => 'comment-2',
          'language' => $language_manager->getLanguage('mr'),
        ]
      )
    );

    // Post a comment on French translation.
    $this->postCommentOnMultilingualContent($node, 'fr');
    $assert_session->addressEquals(
      Url::fromRoute('entity.node.canonical',
        ['node' => $node->id()],
        ['fragment' => 'comment-3']
      )
    );
  }

}
