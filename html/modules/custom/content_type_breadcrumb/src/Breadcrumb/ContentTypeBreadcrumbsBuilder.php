<?php
namespace Drupal\content_type_breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;

class ContentTypeBreadcrumbsBuilder implements BreadcrumbBuilderInterface{

  private $config;
  private $menu_id;
  private $views = [
    'pd_core_ati_details',
    'pd_core_contracts_details',
    'pd_core_grants_details',
    'pd_core_hospitalityq_details',
    'pd_core_inventory_details',
    'pd_core_reclassification_details',
    'pd_core_travela_details',
    'pd_core_travelq_details',
    'pd_core_wrongdoing_details',
    ];

  public function __construct() {
    $this->config = \Drupal::config('content_type_breadcrumb.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
   try {
      $parameters = $attributes->getParameters()->all();
      $type = key($parameters);
      $menu_for_type = $this->config->get('content_type_breadcrumb');

      // apply breadcrumb for content types
      if ($type === "node" && method_exists($parameters[$type], 'getType')) {
        $content_type = $parameters[$type]->getType();
        if (array_key_exists('type_' . $content_type, $menu_for_type) && $menu_for_type['type_' . $content_type] != '') {
          $this->menu_id = str_replace("main:", '', $menu_for_type['type_' . $content_type]);
          return true;
        }
      }

      // apply breadcrumb for views
      if ($type === 'view_id'
        && in_array($parameters[$type], $this->views)
        && isset($menu_for_type['view_' . $parameters[$type]])) {
        $view_id = $parameters[$type];
        $this->menu_id = str_replace("main:", '', $menu_for_type['view_' . $view_id]);
        return true;
      }

      // apply breadcrumb for taxonomy terms
      if ($attributes->getRouteName() == 'entity.taxonomy_term.canonical'
       && $attributes->getParameter('taxonomy_term') instanceof TermInterface) {
        $vocabulary = $attributes->getParameter('taxonomy_term')->bundle();
        if (array_key_exists('vocabulary_' . $vocabulary, $menu_for_type) && $menu_for_type['vocabulary_' . $vocabulary] != '') {
          $this->menu_id = str_replace("main:", '', $menu_for_type['vocabulary_' . $vocabulary]);
          return true;
        }
      }

   } catch (\Exception $e) {
      \Drupal::logger('content breadcrumb')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    try {
      $breadcrumb = new Breadcrumb();
      if (count(explode(':', $this->menu_id)) === 1) return $breadcrumb;
      
      $menu_tree = [];
      $id = $this->menu_id;

      do {
        $parent_menu = $menu_link_manager->createInstance($id);
        if ($parent_menu) {
          $menu_tree[$parent_menu->getTitle()] = $parent_menu->getUrlObject();
          if ($parent_menu->getUrlObject()->isRouted()) {
            // internal links
            $url = $parent_menu->getUrlObject()->getRouteName();
            $route_params = $parent_menu->getUrlObject()->getRouteParameters();
            $links[] = Link::createFromRoute($parent_menu->getTitle(), $url, $route_params);
          }
          else {
            // external links
            $url = $parent_menu->getUrlObject()->getUri();
            $links[] = Link::fromTextAndUrl($parent_menu->getTitle(), Url::fromUri($url));
          }
          $id = $parent_menu->getPluginId();
        }
      } while (!empty($id = $menu_link_manager->createInstance($id)->getParent()));

      $reversed = array_reverse($links);
      $breadcrumb->setLinks($reversed);

    } catch (\Exception $e) {
      // if experience any errors in creating breadcrumb then default to homepage and report error to watchdog
      $breadcrumb = new Breadcrumb();
      $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));
      \Drupal::logger('content breadcrumb')->error($e->getMessage());
    }

    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }
}
