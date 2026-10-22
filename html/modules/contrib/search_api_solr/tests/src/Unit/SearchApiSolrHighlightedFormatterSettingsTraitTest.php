<?php

namespace Drupal\Tests\search_api_solr\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Drupal\search_api_solr\Plugin\Field\FieldFormatter\SearchApiSolrHighlightedFormatterSettingsTrait;

/**
 * Tests highlighted formatter settings helpers.
 *
 * @group search_api_solr
 */
class SearchApiSolrHighlightedFormatterSettingsTraitTest extends Drupal10CompatibilityUnitTestCase {

  /**
   * Ensures string query keys are handled like a single strict key.
   */
  public function testGetHighlightedValueWithStringQueryKeys(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('getKeys')->willReturn('Foo');
    if ($query instanceof CacheableDependencyInterface) {
      $query->method('getCacheContexts')->willReturn([]);
      $query->method('getCacheTags')->willReturn([]);
      $query->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);
    }

    $result_item = $this->createMock(ItemInterface::class);
    $result_item->method('getId')->willReturn('entity:test_entity/123:en');
    $result_item->method('getExtraData')
      ->with('highlighted_keys')
      ->willReturn(['Foo']);

    $result_set = $this->createMock(ResultSetInterface::class);
    $result_set->method('getQuery')->willReturn($query);
    $result_set->method('getResultItems')->willReturn([$result_item]);

    $query_helper = $this->createMock(QueryHelperInterface::class);
    $query_helper->method('getAllResults')->willReturn([$result_set]);

    $container = new ContainerBuilder();
    $container->set('search_api.query_helper', $query_helper);
    \Drupal::setContainer($container);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('test_entity');
    $entity->method('id')->willReturn('123');
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('getLangcode')->willReturn('en');
    $item->method('getEntity')->willReturn($entity);

    $formatter = new class() extends SearchApiSolrHighlightedFormatterSettingsTraitTestFormatterBase {
      use SearchApiSolrHighlightedFormatterSettingsTrait;

      /**
       * Returns a formatter setting.
       */
      public function getSetting($name) {
        return [
          'prefix' => '<strong>',
          'suffix' => '</strong>',
          'strict' => TRUE,
        ][$name];
      }

      /**
       * Gets the highlighted value.
       */
      public function highlightedValue(FieldItemInterface $item, string $value, string $langcode, CacheableMetadata $cacheable_metadata): string {
        return $this->getHighlightedValue($item, $value, $langcode, $cacheable_metadata);
      }

    };

    $cacheable_metadata = new CacheableMetadata();

    $this->assertSame(
      '<strong>Foo</strong> bar',
      $formatter->highlightedValue($item, 'Foo bar', 'en', $cacheable_metadata)
    );
  }

}

/**
 * Base formatter class for testing the highlighted formatter settings trait.
 */
class SearchApiSolrHighlightedFormatterSettingsTraitTestFormatterBase {

  /**
   * Returns default formatter settings.
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * Returns the formatter settings form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
