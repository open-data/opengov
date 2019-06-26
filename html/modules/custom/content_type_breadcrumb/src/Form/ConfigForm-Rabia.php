<?php

namespace Drupal\content_type_breadcrumb\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SortArray;

class ConfigForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ConfigForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // generate a list of content types
      $contentTypes = NodeType::loadMultiple();
      foreach ($contentTypes as $contentType) {
        $id = $contentType->id();

        // load main navigation since it is used to generate menu_breadcrumb
//        $links = [];
        $menu_titles = [];
        $menu_name = 'main';
        $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
        $menu_links = $storage->loadByProperties(['menu_name' => $menu_name]);

        if (!empty($menu_links)) {
          foreach ($menu_links as $mlid => $menu_link) {
/*            $link = [];
            $link['type'] = 'menu_link';
            $link['mlid'] = $menu_link->id->value;
            $link['plid'] = $menu_link->parent->value ?? '0';
            $link['menu_name'] = $menu_link->menu_name->value;
            $link['link_title'] = $menu_link->title->value;
            $link['uri'] = $menu_link->link->uri;
            $link['options'] = $menu_link->link->options;
            $link['weight'] = $menu_link->weight->value;
            $links[] = $link;
*/            $menu_titles[$menu_link->id->value] = $menu_link->title->value . '|' . $menu_link->link->uri;
          }
          // Sort menu links by weight element
//          usort($links, [SortArray::class, 'sortByWeightElement']);
        }

        $form['type_' . $id] = [
          '#type' => 'select',
          '#title' => $contentType->label(),
          '#options' => $menu_titles,
          '#description' => $this->t('Select parent menu'),
        ];
      }

      // submit button
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];

    } catch (\Exception $e) {
      \Drupal::logger('php')->error($e->getMessage() . $e->getLine());
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $contentTypes = NodeType::loadMultiple();
    foreach ($contentTypes as $contentType) {
      $form_element = $form_state->getValue('type_' . $contentType->id());
      drupal_set_message($contentType->id() . ' - ' . $form_element);
    }
//      $breadcrumb = new Breadcrumb();
//      $breadcrumb->addCacheContexts(['url.path.parent']);
//      $breadcrumb->setLinks($links);

  }
}
