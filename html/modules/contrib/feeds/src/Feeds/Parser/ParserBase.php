<?php

namespace Drupal\feeds\Feeds\Parser;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * Base class for Feeds parsers.
 */
abstract class ParserBase extends PluginBase implements ParserInterface, MappingPluginFormInterface {

  /**
   * Returns the label for single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if the source has a special name. Null otherwise.
   */
  protected function configSourceLabel() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state) {
    // Override the label for adding new sources, so it is more clear what the
    // source value represents.
    $source_label = $this->configSourceLabel();
    if ($source_label) {
      foreach (Element::children($form['mappings']) as $i) {
        if (!isset($form['mappings'][$i]['map'])) {
          continue;
        }
        foreach (Element::children($form['mappings'][$i]['map']) as $subtarget) {
          $form['mappings'][$i]['map'][$subtarget]['select']['#options']['__new'] = $this->t('New @label...', [
            '@label' => $source_label,
          ]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state) {}

}
