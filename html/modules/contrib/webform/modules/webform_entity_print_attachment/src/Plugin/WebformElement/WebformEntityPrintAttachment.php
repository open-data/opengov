<?php

namespace Drupal\webform_entity_print_attachment\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentBase;

/**
 * Provides a 'webform_entity_print_attachment' element.
 *
 * @WebformElement(
 *   id = "webform_entity_print_attachment",
 *   label = @Translation("Attachment print document"),
 *   description = @Translation("Attaches submission's print document."),
 *   category = @Translation("File attachment elements"),
 *   deriver = "\Drupal\webform_entity_print_attachment\Plugin\Derivative\WebformEntityPrintAttachmentDeriver",
 * )
 */
class WebformEntityPrintAttachment extends WebformAttachmentBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      'view_mode' => 'html',
    ];

    // PDF documents should never be trimmed.
    unset($properties['trim']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::finalize($element, $webform_submission);
    // Explode element_type:export_type.
    // @see \Drupal\webform_entity_print_attachment\Element\WebformEntityPrintAttachment::getExportTypeId
    list($element['#type'], $element['#export_type']) = explode(':', $element['#type']);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Require export type file extension.
    $file_extension = $this->getExportTypeFileExtension();
    $t_args = ['@extension' => $file_extension];
    $form['attachment']['filename']['#description'] .= '<br/><br/>' . $this->t('File name must include *.@extension file extension.', $t_args);
    $form['attachment']['filename']['#pattern'] = '^.*\.' . $file_extension . '$';
    $form['attachment']['filename']['#pattern_error'] = $this->t('File name must include *.@extension file extension.', $t_args);
    WebformElementHelper::process($form['attachment']['filename']);

    // View mode.
    $form['attachment']['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => [
        'html' => $this->t('HTML'),
        'table' => $this->t('Table'),
      ],
    ];

    return $form;
  }

  /**
   * Get export type file extension.
   *
   * @return string
   *   An export type file extension.
   */
  protected function getExportTypeFileExtension() {
    /** @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface $export_type_manager */
    $export_type_manager = \Drupal::service('plugin.manager.entity_print.export_type');
    list(, $export_type_id) = explode(':', $this->getPluginId());
    $definition = $export_type_manager->getDefinition($export_type_id);
    return $definition['file_extension'];
  }

}
