<?php

namespace Drupal\bootstrap_layouts;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BootstrapLayoutsPluginManager extends DefaultPluginManager implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * Base plugin manager for Bootstrap Layouts plugin managers.
   *
   * The "container.namespaces" service does not contain theme namespaces
   * since themes are not registered in the container. To allow themes to be
   * able to participate in these plugins, the normal "namespaces" provided
   * must be appending with the missing autoloader prefixes of the themes.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme manager used to invoke the alter hook with.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager used to invoke the alter hook with.
   * @param string|null $plugin_interface
   *   (optional) The interface each plugin should implement.
   * @param string $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager, $plugin_interface = NULL, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin') {
    /** @var \Composer\Autoload\ClassLoader $class_loader */
    $class_loader = \Drupal::service('class_loader');

    /** @var \ArrayObject $namespaces */
    $ns = $namespaces->getArrayCopy();

    foreach ($class_loader->getPrefixesPsr4() as $prefix => $paths) {
      // Remove trailing path separators.
      $prefix = trim($prefix, '\\');

      // Remove the DRUPAL_ROOT prefix.
      $path = str_replace(\Drupal::root() . '/', '', reset($paths));

      // Only add missing contrib theme namespaces.
      if (preg_match('/^(core|vendor)/', $path) === 0 && !isset($namespaces[$prefix])) {
        $ns[$prefix] = $path;
      }
    }

    // Replace the namespaces data.
    $namespaces->exchangeArray($ns);

    // Construct the plugin manager now.
    parent::__construct('Plugin/BootstrapLayouts', $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    // Set the theme handler and manager.
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('container.namespaces'),
      $container->get('cache.discovery'),
      $container->get('module_handler'),
      $container->get('theme_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container): void {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    if ($this->alterHook) {
      $this->moduleHandler->alter($this->alterHook, $definitions);
      $this->themeManager->alter($this->alterHook, $definitions);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

}
