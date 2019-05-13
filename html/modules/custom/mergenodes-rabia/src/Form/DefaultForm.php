<?php

namespace Drupal\mergenodes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mergeNodesForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // generate a list of content types
    $all_content_types = NodeType::loadMultiple();
    $content_types = array();
    foreach ($all_content_types as $machine_name => $content_type) {
      $content_types[$content_type->id()] = $content_type->label();
    }
    ksort($content_types);

    // create a simple multi select list of content types
    $form['contenttype'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a content type to merge'),
      '#options' => $content_types,
      '#size' => count($content_types),
      '#weight' => '0',
    ];

    if ($form_state->isSubmitted()) {
      // get selected content type
      $content_type = $form_state->getValue('contenttype');
      if (empty($content_type)) {
        drupal_set_message("No content type selected for mapping", 'warning');
      }
      else {
        // fetch all nodes of content type
        $query = \Drupal::entityQuery('node');
        $query->condition('type', $content_type);
        $query->exists('field_previousnodeid');
        $query->sort('field_previousnodeid');
        $query->sort('langcode');
        $nids = $query->execute();
        $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

        // generate a mapping table of translations
        $rows = array();
        foreach ($nodes as $node) {
          if ($node->get('langcode')->value == 'en') {
            foreach ($nodes as $node_translation) {
              if (($node_translation->get('field_previousnodeid')->value == ($node->get('field_previousnodeid')->value))
                and ($node_translation->get('langcode')->value == 'fr')) {
                $rows[] = array(
                  $node->get('title')->value,
                  $node_translation->get('title')->value,
                );
              }
            }
          }
        }

        // generate a table of mappings to render
        $form['mapping'] = [
          '#type' => 'table',
          '#header' => [$this->t('English'), $this->t('French')],
          '#rows' => $rows,
        ];

      }
    }

    // button to view node mapping
    $form['view_mapping'] = array(
      '#name' => 'view_mappings',
      '#type' => 'submit',
      '#value' => t('View Mapping'),
      '#submit' => array([$this, 'viewMappings']),
    );

    // submit button
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Merge translations'),
    ];

    return $form;
  }

  public function viewMappings(array &$form, FormStateInterface &$form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_type = $form_state->getValue('contenttype');
    if (empty($content_type)) {
      drupal_set_message('No content type selected to merge', 'warning');
    }
    else {
      // 1. Fetch all nodes of content type
      $query = \Drupal::entityQuery('node');
      $query->condition('type', $content_type);
      $query->exists('field_previousnodeid');
      $query->sort('field_previousnodeid');
      $query->sort('langcode');
      $nids = $query->execute();
      $keys = array_keys($nids);
      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");

      for($i = 0; $i < sizeof($keys); $i++) {
        // 2. Load the field_previousnodeid for the first node
        $node1 = $storage_handler->load($nids[$keys[$i]]);
        $node1_previousnodeid = $node1->get('field_previousnodeid')->getValue();
        $node1_langcode = $node1->language()->getId();

        // 3. check the field_previousnodeid for the next node
        $node2 = $storage_handler->load($nids[$keys[$i+1]]);
        if ($node2) {
          $node2_previousnodeid = $node2->get('field_previousnodeid')->getValue();
          $node2_langcode = $node2->language()->getId();

          if (($node1_previousnodeid[0]['value'] == $node2_previousnodeid[0]['value'])
            and ($node1_langcode != $node2_langcode)) {
            // 4. If both have same value for field_previousnodeid merge the second node as a translation and delete after merging
            $node1->addTranslation($node2_langcode, $node2->getTranslation($node2_langcode)->toArray());
            $node1->save();
            $node2->delete();
            drupal_set_message('Merged translation' . $node2->get('title')->value . ' with ' . $node1->get('title')->value );

            // move counter one ahead
            $i++;
          }
          else {
            drupal_set_message('No translation found for ' . $node1->get('title')->value, 'error');
          }
        }
      }
    }

  }

}
