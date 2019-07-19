<?php
namespace Drupal\content_type_breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CommitmentBreadcrumbsBuilder implements BreadcrumbBuilderInterface{

  private $menu_id;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
   try {
      $parameters = $attributes->getParameters()->all();
      $type = key($parameters);

      // apply breadcrumb for commitment content type
      // for all other content types see class ContentTypeBreadcrumbsBuilder
      if ( $type === "node"
        && method_exists($parameters[$type], 'getType')
        && $parameters[$type]->getType() === 'commitment'
        && $parameters[$type]->field_reference_landing){
          // load the menu of field_reference_landing in the commitment type as parent menu item
          $node_id = $parameters[$type]->field_reference_landing->__get('target_id');
          $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
          $this->menu_id = key($menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $node_id), 'main'));
          return true;
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
