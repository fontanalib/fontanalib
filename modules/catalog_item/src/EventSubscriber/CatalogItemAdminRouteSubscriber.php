<?php

namespace Drupal\catalog_item\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _admin_route for specific catalog_item-related routes.
 */
class CatalogItemAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * Constructs a new CatalogItemAdminRouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $router_builder) {
    $this->configFactory = $config_factory;
    $this->routerBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('catalog_item.settings')->get('catalog_use_admin_theme')) {
      foreach ($collection->all() as $route) {
        if ($route->hasOption('_catalog_item_operation_route')) {
          $route->setOption('_admin_route', TRUE);
        }
      }
    }
  }

  /**
   * Rebuilds the router when catalog_item.settings:use_admin_theme is changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if ($event->getConfig()->getName() === 'catalog_item.settings' && $event->isChanged('catalog_use_admin_theme')) {
      $this->routerBuilder->setRebuildNeeded();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    return $events;
  }

}
