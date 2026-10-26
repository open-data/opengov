<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;

/**
 * Shared config/form handling for entity-translation access checking.
 */
trait TranslateEntityAccessCheckTrait {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      parent::defaultConfiguration(),
      ['skip_access_check' => FALSE]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);
    $configuration = $this->getConfiguration();

    $form['skip_access_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip access checking'),
      '#description' => $this->t('If selected, facet entity access checks will be bypassed.'),
      '#default_value' => !empty($configuration['skip_access_check']),
    ];

    return $form;
  }

}
