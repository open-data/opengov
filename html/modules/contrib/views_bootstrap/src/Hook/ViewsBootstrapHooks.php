<?php

namespace Drupal\views_bootstrap\Hook;

use Drupal\views_bootstrap\ViewsBootstrap;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for views_bootstrap.
 */
class ViewsBootstrapHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.views_bootstrap':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The <a href="https://www.drupal.org/project/views_bootstrap">Views Bootstrap module</a> adds styles to Views to output the results of a view as several common <a href="http://getbootstrap.com/components/">Twitter Bootstrap</a> components.') . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<p>' . $this->t('<a href="/admin/structure/views/add">Create a view</a> using one of the following styles:') . '</p>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/components/card/">Card</a>') . '</li>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/layout/grid/">Grid</a>') . '</li>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/content/tables/">Tables</a>') . '</li>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/components/media-object/">Media object</a>') . '</li>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/components/collapse/#accordion-example">Accordion</a>') . '</li>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/components/carousel/">Carousel</a>') . '</li>';
        $output .= '<li>' . $this->t('<a href="https://getbootstrap.com/docs/5.1/components/list-group/">List group</a>') . '</li>';
        $output .= '<ul>';
        return $output;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme() {
    return ViewsBootstrap::getThemeHooks();
  }

}
