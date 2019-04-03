<?php

namespace Drupal\vud\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\votingapi\Entity\VoteType;

/**
 * Plugin implementation of the 'vote_up_down_field' field type.
 *
 * @FieldType(
 *   id = "vote_up_down_field",
 *   label = @Translation("Vote up down field"),
 *   module = "vote_up_down",
 *   description = @Translation("Field type to display the widgets in the view."),
 *   default_widget = "vote_up_down_widget_type",
 *   default_formatter = "vote_up_down_formatter_type"
 * )
 */
class VoteUpDownField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'widget' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Widget Template'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];

    return $schema;
  }

  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $widgets = \Drupal::service('plugin.manager.vud')->getDefinitions();
    $widgets_options = [];
    foreach ($widgets as $vote_plugin) {
      $widgets_options[$vote_plugin['id']] = $vote_plugin['admin_label'];
    }

    $element['widget'] = array(
      '#type' => 'select',
      '#title' => t('Vote Up/Down widget'),
      '#options' => $widgets_options,
      '#required' => TRUE,
      '#default_value' => $this->getSetting('widget'),
      '#disabled' => $has_data,
    );

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
