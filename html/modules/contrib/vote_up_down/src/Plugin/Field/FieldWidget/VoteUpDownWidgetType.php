<?php

namespace Drupal\vud\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vote_up_down_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "vote_up_down_widget_type",
 *   label = @Translation("Vote up down widget type"),
 *   field_types = {
 *     "vote_up_down_field"
 *   }
 * )
 */
class VoteUpDownWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'vote_tag' => 'vote',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    return $element;
  }

}
