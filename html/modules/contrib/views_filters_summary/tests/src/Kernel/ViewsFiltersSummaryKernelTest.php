<?php

namespace Drupal\Tests\views_filters_summary\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ViewsFiltersSummary area plugin.
 *
 * @group views_filters_summary
 */
#[Group('views_filters_summary')]
#[RunTestsInSeparateProcesses]
class ViewsFiltersSummaryKernelTest extends ViewsKernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use UserCreationTrait;

  use NodeCreationTrait {
    createNode as drupalCreateNode;
  }

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_vfs_summary'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'text',
    'filter',
    'system',
    'taxonomy',
    'views',
    'views_filters_summary',
    'views_filters_summary_test',
  ];

  /**
   * The vocabulary used for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * The first taxonomy term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term1;

  /**
   * The second taxonomy term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term2;

  /**
   * Created nodes for testing.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'filter', 'taxonomy']);

    // Set up an admin user for node access.
    $admin = $this->createUser([], NULL, TRUE);
    $this->container->get('current_user')->setAccount($admin);

    // Create content types.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create taxonomy vocabulary and terms.
    $this->vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $this->vocabulary->save();

    $this->term1 = Term::create([
      'name' => 'Term A',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->term1->save();

    $this->term2 = Term::create([
      'name' => 'Term B',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->term2->save();

    // Create entity reference field on article.
    $handler_settings = [
      'target_bundles' => [
        'tags' => 'tags',
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_tags',
      'Tags',
      'taxonomy_term',
      'default',
      $handler_settings,
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create 3 articles.
    $this->nodes[] = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Article one',
      'status' => 1,
      'field_tags' => [['target_id' => $this->term1->id()]],
    ]);
    $this->nodes[] = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Article two',
      'status' => 1,
      'field_tags' => [['target_id' => $this->term2->id()]],
    ]);
    $this->nodes[] = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Article three',
      'status' => 0,
      'field_tags' => [
        ['target_id' => $this->term1->id()],
        ['target_id' => $this->term2->id()],
      ],
    ]);
    // Create 2 pages.
    $this->nodes[] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Page one',
      'status' => 1,
    ]);
    $this->nodes[] = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Page two',
      'status' => 1,
    ]);

    if ($import_test_views) {
      ViewTestData::createTestViews(
        static::class,
        ['views_filters_summary_test']
      );
    }
  }

  /**
   * Renders a view with the given exposed input.
   *
   * @param array $exposed_input
   *   The exposed input to set on the view.
   * @param string $display_id
   *   The display ID.
   *
   * @return string
   *   The rendered HTML.
   */
  protected function renderViewWithInput(
    array $exposed_input = [],
    string $display_id = 'page_1',
  ): string {
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay($display_id);
    if ($exposed_input) {
      $view->setExposedInput($exposed_input);
    }
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    return (string) $this->container->get('renderer')
      ->renderRoot($output);
  }

  /**
   * Tests no summary appears without exposed input.
   */
  public function testNoSummaryWithoutExposedInput(): void {
    $html = $this->renderViewWithInput();

    // @total should be present — all 5 nodes.
    $this->assertStringContainsString('Displaying 5 results', $html);
    // No filter summary should be present.
    $this->assertStringNotContainsString('views-filters-summary', $html);
  }

  /**
   * Tests string filter value appears in summary.
   */
  public function testStringFilter(): void {
    $html = $this->renderViewWithInput(['title' => 'Article one']);

    $this->assertStringContainsString('Article one', $html);
    $this->assertStringContainsString('views-filters-summary', $html);
  }

  /**
   * Tests string filter operator labels.
   */
  public function testStringFilterOperators(): void {
    $operators = [
      '!=' => ['value' => 'Article one', 'expected' => 'Not Article one'],
      'contains' => ['value' => 'Article', 'expected' => 'Contains Article'],
      'starts' => ['value' => 'Article', 'expected' => 'Starts with Article'],
      'ends' => ['value' => 'one', 'expected' => 'Ends with one'],
    ];

    foreach ($operators as $operator => $info) {
      $view = Views::getView('test_vfs_summary');
      $view->setDisplay('page_1');

      // Override the title filter operator.
      $filters = $view->displayHandlers->get('default')
        ->getOption('filters');
      $filters['title']['operator'] = $operator;
      $view->displayHandlers->get('default')
        ->overrideOption('filters', $filters);

      $view->setExposedInput(['title' => $info['value']]);
      $view->preExecute();
      $view->execute();
      $output = $view->render();
      $html = (string) $this->container->get('renderer')
        ->renderRoot($output);

      $this->assertStringContainsString(
        $info['expected'],
        $html,
        "Operator '$operator' should produce '{$info['expected']}'."
      );
    }
  }

  /**
   * Tests bundle filter shows human-readable label.
   */
  public function testBundleFilter(): void {
    $html = $this->renderViewWithInput(['type' => ['article']]);

    // Should show "Article" not "article".
    $this->assertStringContainsString('Article', $html);
    $this->assertStringContainsString('views-filters-summary', $html);
  }

  /**
   * Tests boolean filter shows value label.
   */
  public function testBooleanFilter(): void {
    $html = $this->renderViewWithInput(['status' => '1']);

    $this->assertStringContainsString('views-filters-summary', $html);
    // Boolean filter with value "1" shows "Yes".
    $this->assertStringContainsString('Yes', $html);
  }

  /**
   * Tests taxonomy term filter shows term labels.
   */
  public function testTaxonomyFilter(): void {
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');

    // Add a taxonomy_index_tid filter.
    $filters = $view->displayHandlers->get('default')
      ->getOption('filters');
    $filters['tid'] = [
      'id' => 'tid',
      'table' => 'taxonomy_index',
      'field' => 'tid',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'or',
      'value' => [],
      'group' => 1,
      'exposed' => TRUE,
      'expose' => [
        'operator_id' => 'tid_op',
        'label' => 'Tags',
        'description' => '',
        'use_operator' => FALSE,
        'operator' => 'tid_op',
        'identifier' => 'tid',
        'required' => FALSE,
        'remember' => FALSE,
        'multiple' => TRUE,
        'remember_roles' => [
          'authenticated' => 'authenticated',
        ],
        'reduce' => FALSE,
      ],
      'is_grouped' => FALSE,
      'group_info' => [
        'label' => '',
        'description' => '',
        'identifier' => '',
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [],
      ],
      'reduce_duplicates' => FALSE,
      'type' => 'select',
      'limit' => TRUE,
      'vid' => 'tags',
      'hierarchy' => FALSE,
      'error_message' => TRUE,
      'plugin_id' => 'taxonomy_index_tid',
    ];
    $view->displayHandlers->get('default')
      ->overrideOption('filters', $filters);

    $view->setExposedInput([
      'tid' => [$this->term1->id()],
    ]);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    // Should show the term label, not the tid.
    $this->assertStringContainsString('Term A', $html);
    $this->assertStringContainsString('views-filters-summary', $html);
  }

  /**
   * Tests show_labels option.
   */
  public function testShowLabelsOption(): void {
    // Default: show_labels is FALSE — no label in summary.
    $html = $this->renderViewWithInput(['title' => 'Article']);
    $this->assertStringContainsString('views-filters-summary', $html);
    $this->assertStringNotContainsString(
      '<span class="label">',
      $html
    );

    // Enable show_labels.
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');
    $header = $view->displayHandlers->get('default')
      ->getOption('header');
    $header['views_filters_summary']['show_labels'] = TRUE;
    $view->displayHandlers->get('default')
      ->overrideOption('header', $header);

    $view->setExposedInput(['title' => 'Article']);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    $this->assertStringContainsString('Title:', $html);
  }

  /**
   * Tests remove and reset links.
   */
  public function testRemoveAndResetLinks(): void {
    // Default: show_remove_link = TRUE, show_reset_link = FALSE.
    $html = $this->renderViewWithInput(['title' => 'Article']);
    $this->assertStringContainsString('remove-filter', $html);
    $this->assertStringNotContainsString(
      'class="reset disabled"',
      $html
    );

    // Enable reset link, disable remove link.
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');
    $header = $view->displayHandlers->get('default')
      ->getOption('header');
    $header['views_filters_summary']['show_remove_link'] = FALSE;
    $header['views_filters_summary']['show_reset_link'] = TRUE;
    $header['views_filters_summary']['filters_reset_link_title'] = 'Clear all';
    $view->displayHandlers->get('default')
      ->overrideOption('header', $header);

    $view->setExposedInput(['title' => 'Article']);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    $this->assertStringNotContainsString('remove-filter', $html);
    $this->assertStringContainsString('Clear all', $html);
    $this->assertStringContainsString('class="reset disabled"', $html);
  }

  /**
   * Tests filter selection option.
   */
  public function testFilterSelectionOption(): void {
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');

    // Limit summary to only the "type" filter.
    $header = $view->displayHandlers->get('default')
      ->getOption('header');
    $header['views_filters_summary']['filters'] = ['type'];
    $view->displayHandlers->get('default')
      ->overrideOption('header', $header);

    $view->setExposedInput([
      'type' => ['article'],
      'title' => 'Article',
    ]);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    // "Article" should appear (type filter). The title filter value
    // should not appear in the summary since only "type" is selected.
    $this->assertStringContainsString('Article', $html);
    $this->assertStringNotContainsString(
      'Contains Article',
      $html
    );
  }

  /**
   * Tests custom prefix and separator.
   */
  public function testPrefixAndSeparator(): void {
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');

    $header = $view->displayHandlers->get('default')
      ->getOption('header');
    $header['views_filters_summary']['filters_summary_prefix'] = 'filtered by ';
    $header['views_filters_summary']['filters_summary_separator'] = ' | ';
    $view->displayHandlers->get('default')
      ->overrideOption('header', $header);

    $view->setExposedInput([
      'type' => ['article'],
      'title' => 'Article',
    ]);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    $this->assertStringContainsString('filtered by', $html);
    $this->assertStringContainsString(' | ', $html);
  }

  /**
   * Tests singular/plural result label.
   */
  public function testResultLabel(): void {
    // Multiple results — should show "results" (plural).
    $html = $this->renderViewWithInput();
    $this->assertStringContainsString('5 results', $html);

    // Single result — should show "result" (singular).
    $html = $this->renderViewWithInput(['title' => 'Article one']);
    $this->assertStringContainsString('1 result', $html);
    $this->assertStringNotContainsString('1 results', $html);
  }

  /**
   * Tests empty results behavior.
   */
  public function testEmptyResults(): void {
    // With empty=false (default), no summary on 0 results.
    $html = $this->renderViewWithInput([
      'title' => 'nonexistent-content-xyz',
    ]);
    $this->assertStringNotContainsString('Displaying 0', $html);

    // With empty=true, summary should still render.
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');
    $header = $view->displayHandlers->get('default')
      ->getOption('header');
    $header['views_filters_summary']['empty'] = TRUE;
    $view->displayHandlers->get('default')
      ->overrideOption('header', $header);

    $view->setExposedInput(['title' => 'nonexistent-content-xyz']);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    $this->assertStringContainsString('Displaying 0', $html);
  }

  /**
   * Tests grouped filter displays the group item title.
   */
  public function testGroupedFilter(): void {
    $view = Views::getView('test_vfs_summary');
    $view->setDisplay('page_1');

    // Replace filters with a grouped type filter.
    $filters = $view->displayHandlers->get('default')
      ->getOption('filters');
    $filters['type'] = [
      'id' => 'type',
      'table' => 'node_field_data',
      'field' => 'type',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'in',
      'value' => [],
      'group' => 1,
      'exposed' => TRUE,
      'expose' => [
        'operator_id' => 'type_op',
        'label' => 'Content type',
        'description' => '',
        'use_operator' => FALSE,
        'operator' => 'type_op',
        'identifier' => 'type',
        'required' => FALSE,
        'remember' => FALSE,
        'multiple' => FALSE,
        'remember_roles' => [
          'authenticated' => 'authenticated',
        ],
        'reduce' => FALSE,
      ],
      'is_grouped' => TRUE,
      'group_info' => [
        'label' => 'Content type',
        'description' => '',
        'identifier' => 'type',
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [
          1 => [
            'title' => 'Articles only',
            'operator' => 'in',
            'value' => ['article' => 'article'],
          ],
          2 => [
            'title' => 'Pages only',
            'operator' => 'in',
            'value' => ['page' => 'page'],
          ],
        ],
      ],
      'plugin_id' => 'bundle',
      'entity_type' => 'node',
      'entity_field' => 'type',
    ];
    // Keep only the grouped type filter for clarity.
    unset($filters['title'], $filters['status']);
    $view->displayHandlers->get('default')
      ->overrideOption('filters', $filters);

    $view->setExposedInput(['type' => '1']);
    $view->preExecute();
    $view->execute();
    $output = $view->render();
    $html = (string) $this->container->get('renderer')
      ->renderRoot($output);

    // Should show the group item's title, not the machine name.
    $this->assertStringContainsString('Articles only', $html);
    $this->assertStringContainsString('views-filters-summary', $html);
  }

}
