<?php

namespace Drupal\facets\Plugin\facets\hierarchy;

use Drupal\facets\Hierarchy\HierarchyPluginBase;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Taxonomy hierarchy.
 *
 * @FacetsHierarchy(
 *   id = "taxonomy",
 *   label = @Translation("Taxonomy hierarchy"),
 *   description = @Translation("Hierarchy structure provided by the taxonomy module.")
 * )
 */
class Taxonomy extends HierarchyPluginBase {

  /**
   * Static cache for the nested children.
   *
   * @var array
   */
  protected $nestedChildren = [];

  /**
   * Static cache for the term parents.
   *
   * @var array
   */
  protected $termParents = [];

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\taxonomy\TermStorageInterface $termStorage
   *   The term storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TermStorageInterface $termStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->termStorage = $termStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getParentIds($id) {
    $current_tid = $id;
    while ($parent = $this->taxonomyGetParent($current_tid)) {
      $current_tid = $parent;
      $parents[$id][] = $parent;
    }
    return isset($parents[$id]) ? $parents[$id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNestedChildIds($id) {
    if (isset($this->nestedChildren[$id])) {
      return $this->nestedChildren[$id];
    }

    $children = $this->termStorage->loadChildren($id);
    $children = array_filter(array_values(array_map(function ($it) {
      return $it->id();
    }, $children)));

    $subchilds = [];
    foreach ($children as $child) {
      $subchilds = array_merge($subchilds, $this->getNestedChildIds($child));
    }
    return $this->nestedChildren[$id] = array_merge($children, $subchilds);
  }

  /**
   * {@inheritdoc}
   */
  public function getChildIds(array $ids) {
    $parents = [];
    foreach ($ids as $id) {
      $terms = $this->termStorage->loadChildren($id);
      $parents[$id] = array_filter(array_values(array_map(function ($it) {
        return $it->id();
      }, $terms)));
    }
    $parents = array_filter($parents);
    return $parents;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiblingIds(array $ids, array $activeIds = [], bool $parentSiblings = TRUE) {
    if (empty($ids)) {
      return [];
    }

    $parentIds = [];
    $topLevelTerms = [];

    foreach ($ids as $id) {
      if (!$activeIds || in_array($id, $activeIds)) {
        $currentParentIds = $this->getParentIds($id);
        if (!$currentParentIds) {
          if (!$topLevelTerms) {
            /** @var \Drupal\taxonomy\Entity\Term $term */
            $term = $this->termStorage->load($id);
            $topLevelTerms = array_map(function ($term) {
              return $term->tid;
            }, $this->termStorage->loadTree($term->bundle(), 0, 1));
          }
        }
        else {
          $parentIds[] = $currentParentIds;
        }
      }
    }

    $parentIds = array_unique(array_merge([], ...$parentIds));
    $childIds = array_merge([], ...$this->getChildIds($parentIds));

    return array_diff(
      array_merge(
        $childIds,
        $topLevelTerms,
        (!$topLevelTerms && $parentSiblings) ? $this->getSiblingIds($ids, $parentIds) : []
      ),
      $ids
    );
  }

  /**
   * Returns the parent tid for a given tid, or false if no parent exists.
   *
   * @param int $tid
   *   A taxonomy term id.
   *
   * @return int|false
   *   Returns FALSE if no parent is found, else parent tid.
   */
  protected function taxonomyGetParent($tid) {
    if (isset($this->termParents[$tid])) {
      return $this->termParents[$tid];
    }

    $parents = $this->termStorage->loadParents($tid);
    if (empty($parents)) {
      return FALSE;
    }
    return $this->termParents[$tid] = reset($parents)->id();
  }

}
