<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a contextual filter searching through all indexed taxonomy fields.
 *
 * Note: The plugin annotation below is not misspelled. Due to dependency
 * problems, the plugin is not defined here but in
 * search_api_views_plugins_argument_alter().
 *
 * @ingroup views_argument_handlers
 *
 * ViewsArgument("search_api_term")
 *
 * @see search_api_views_plugins_argument_alter()
 */
class SearchApiTerm extends SearchApiStandard {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setEntityRepository($container->get('entity.repository'));
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));

    return $plugin;
  }

  /**
   * Retrieves the entity repository.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  public function getEntityRepository() {
    return $this->entityRepository ?: \Drupal::service('entity.repository');
  }

  /**
   * Sets the entity repository.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   *
   * @return $this
   */
  public function setEntityRepository(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
    return $this;
  }

  /**
   * Retrieves the term storage.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The term storage.
   */
  public function getTermStorage() {
    if (isset($this->entityTypeManager)) {
      return $this->entityTypeManager->getStorage('taxonomy_term');
    }
    return \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager): static {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    if (!empty($this->argument)) {
      $this->fillValue();
      $terms = [];
      foreach ($this->value as $tid) {
        $taxonomy_term = $this->getTermStorage()->load($tid);
        if ($taxonomy_term) {
          $terms[] = $this->getEntityRepository()
            ->getTranslationFromContext($taxonomy_term)
            ->label();
        }
      }

      return $terms ? implode(', ', $terms) : $this->argument;
    }
    else {
      return $this->argument;
    }
  }

}
