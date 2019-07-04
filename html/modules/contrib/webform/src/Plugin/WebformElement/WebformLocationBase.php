<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base 'location' element.
 */
abstract class WebformLocationBase extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = [
      'title' => '',
      'default_value' => [],
      'multiple' => FALSE,
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'disabled' => FALSE,
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_items' => $this->getItemsDefaultFormat(),
      'format_items_html' => '',
      'format_items_text' => '',
    ] + $this->getDefaultBaseProperties();

    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      $properties[$composite_key . '__title'] = (string) $composite_element['#title'];
      // The value is always visible and supports a custom placeholder.
      if ($composite_key == 'value') {
        $properties[$composite_key . '__placeholder'] = '';
      }
      else {
        $properties[$composite_key . '__access'] = FALSE;
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Hide all composite elements by default.
    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      if ($composite_key != 'value' && !isset($element['#' . $composite_key . '__access'])) {
        $element['#' . $composite_key . '__access'] = FALSE;
      }
    }

    parent::initialize($element);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Reverted #required label.
    $form['validation']['required']['#description'] = $this->t('Check this option if the user must enter a value.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable() {
    $header = [
      $this->t('Key'),
      $this->t('Title/Placeholder'),
      $this->t('Visible'),
    ];

    $rows = [];
    $composite_elements = $this->getCompositeElements();
    foreach ($composite_elements as $composite_key => $composite_element) {
      $title = (isset($composite_element['#title'])) ? $composite_element['#title'] : $composite_key;
      $type = isset($composite_element['#type']) ? $composite_element['#type'] : NULL;
      $t_args = ['@title' => $title];
      $attributes = ['style' => 'width: 100%; margin-bottom: 5px'];

      $row = [];

      // Key.
      $row[$composite_key . '__key'] = [
        '#markup' => $composite_key,
        '#access' => TRUE,
      ];

      // Title, placeholder, and description.
      if ($type) {
        $row['title_and_description'] = [
          'data' => [
            $composite_key . '__title' => [
              '#type' => 'textfield',
              '#title' => $this->t('@title title', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => $this->t('Enter title…'),
              '#attributes' => $attributes,
            ],
            $composite_key . '__placeholder' => [
              '#type' => 'textfield',
              '#title' => $this->t('@title placeholder', $t_args),
              '#title_display' => 'invisible',
              '#placeholder' => $this->t('Enter placeholder…'),
              '#attributes' => $attributes,
            ],
          ],
        ];
      }
      else {
        $row['title_and_description'] = ['data' => ['']];
      }

      // Access.
      if ($composite_key === 'value') {
        $row[$composite_key . '__access'] = [
          '#type' => 'checkbox',
          '#default_value' => TRUE,
          '#disabled' => TRUE,
          '#access' => TRUE,
        ];
      }
      else {
        $row[$composite_key . '__access'] = [
          '#type' => 'checkbox',
          '#return_value' => TRUE,
        ];
      }

      $rows[$composite_key] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
    ] + $rows;
  }

}
