<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Defines a class to build a listing of webform access group entities.
 *
 * @see \Drupal\webform\Entity\WebformOption
 */
class WebformAccessGroupListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Filter form.
    $build['filter_form'] = $this->buildFilterForm();

    // Display info.
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-access-group-table';

    // Attachments.
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    return [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by keyword.'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-access-group-table',
        'data-summary' => '.webform-access-group-summary',
        'data-item-singlular' => $this->t('access group'),
        'data-item-plural' => $this->t('access groups'),
        'title' => $this->t('Enter a keyword to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getStorage()->getQuery()->count()->execute();
    if (!$total) {
      return [];
    }

    return [
      '#markup' => $this->formatPlural($total, '@total access group', '@total access groups', ['@total' => $total]),
      '#prefix' => '<div class="webform-access-group-summary">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Label/Description');
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['users'] = [
      'data' => $this->t('Users'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['entities'] = [
      'data' => $this->t('Nodes'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['permissions'] = [
      'data' => $this->t('Permissions'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface $entity */

    // Label/Description.
    $row['label'] = [
      'data' => [
        'label' => $entity->toLink($entity->label(), 'edit-form')->toRenderable() + ['#suffix' => '<br/>'],
        'description' => WebformHtmlEditor::checkMarkup($entity->get('description')),
      ],
    ];

    // Type.
    $row['type'] = $entity->getTypeLabel();

    // Users.
    $uids = $entity->getUserIds();
    /** @var \Drupal\user\UserInterface[] $users */
    $users = $uids ? User::loadMultiple($uids) : [];
    $items = [];
    foreach ($users as $user) {
      $items[] = $user->toLink();
    }
    $row['users'] = ['data' => ['#theme' => 'item_list', '#items' => $items]];

    // Entities.
    $source_entities = $entity->getEntityIds();
    $items = [];
    foreach ($source_entities as $source_entity_record) {
      list($source_entity_type, $source_entity_id, $field_name, $webform_id) = explode(':', $source_entity_record);
      $source_entity = \Drupal::entityManager()->getStorage($source_entity_type)->load($source_entity_id);
      $webform = Webform::load($webform_id);
      if ($source_entity && $webform) {
        $items[] = [
          'source_entity' => $source_entity->toLink()->toRenderable(),
          'webform' => ['#prefix' => '<br/>', '#markup' => $webform->label()],
        ];
      }
    }
    $row['entities'] = ['data' => ['#theme' => 'item_list', '#items' => $items]];

    // Permissions.
    $permissions = array_intersect_key([
      'create' => $this->t('Create submissions'),
      'view_any' => $this->t('View any submissions'),
      'update_any' => $this->t('Update any submissions'),
      'delete_any' => $this->t('Delete any submissions'),
      'purge_any' => $this->t('Purge any submissions'),
      'view_own' => $this->t('View own submissions'),
      'update_own' => $this->t('Update own submissions'),
      'delete_own' => $this->t('Delete own submissions'),
      'administer' => $this->t('Administer submissions'),
      'test' => $this->t('Test webform'),
    ], array_flip($entity->get('permissions')));
    $row['permissions'] = ['data' => ['#theme' => 'item_list', '#items' => $permissions]];
    $row = $row + parent::buildRow($entity);

    return [
      'data' => $row,
      'class' => ['webform-form-filter-text-source'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 23,
        'url' => Url::fromRoute('entity.webform_access_group.duplicate_form', ['webform_access_group' => $entity->id()]),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

}
