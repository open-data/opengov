<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Media Object.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_media_object",
 *   title = @Translation("Bootstrap Media Object"),
 *   help = @Translation("Displays rows in a Bootstrap Media Object."),
 *   theme = "views_bootstrap_media_object",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapMediaObject extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['heading_field'] = ['default' => ''];
    $options['image_field'] = ['default' => ''];
    $options['image_placement'] = ['default' => 'first'];
    $options['image_class'] = ['default' => 'start'];
    $options['body_field'] = ['default' => ''];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $fields = $this->displayHandler->getFieldLabels(TRUE);
    $optionalFields = ['' => $this->t('<None>')];
    $optionalFields += $this->displayHandler->getFieldLabels(TRUE);

    $form['help'] = [
      '#markup' => $this->t('The Bootstrap media object displays content with an image item lead with heading and text (<a href=":docs">see documentation</a>).',
        [':docs' => 'https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/views-bootstrap-for-bootstrap-5/media-object']),
      '#weight' => -99,
    ];

    $form['heading_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading field'),
      '#options' => $fields,
      '#required' => FALSE,
      '#default_value' => $this->options['heading_field'],
      '#description' => $this->t('Select the field that will be used as the media object heading.'),
    ];

    $form['image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Image field'),
      '#options' => $fields,
      '#required' => TRUE,
      '#default_value' => $this->options['image_field'],
      '#description' => $this->t('Select the field that will be used as the media object image.'),
    ];

    $form['image_placement'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image Placement'),
      '#options' => [
        'first' => $this->t('Left'),
        'last' => $this->t('Right'),
      ],
      '#default_value' => $this->options['image_placement'],
      '#description' => $this->t('Align the media object image left or right.'),
    ];

    $form['image_class'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image Alignment'),
      '#options' => [
        'start' => $this->t('Top'),
        'center' => $this->t('Middle'),
        'end' => $this->t('Bottom'),
      ],
      '#default_value' => $this->options['image_class'],
      '#description' => $this->t('Align the media object image left or right.'),
    ];

    $form['body_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Body field'),
      '#options' => $optionalFields,
      '#required' => FALSE,
      '#default_value' => $this->options['body_field'],
      '#description' => $this->t('Select the field that will be used as the media object body.'),
    ];

  }

}
