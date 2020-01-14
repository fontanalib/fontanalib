<?php

namespace Drupal\catalog_item\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for catalog_items.
 */
class CatalogItemRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/catalog-item/{catalog_item}'))
      ->addDefaults([
        '_controller' => '\Drupal\catalog_item\Controller\CatalogItemViewController::view',
        '_title_callback' => '\Drupal\catalog_item\Controller\CatalogItemViewController::title',
      ])
      ->setRequirement('catalog_item', '\d+')
      ->setRequirement('_entity_access', 'catalog_item.view');
    $route_collection->add('entity.catalog_item.canonical', $route);

    $route = (new Route('/catalog-item/{catalog_item}/delete'))
      ->addDefaults([
        '_entity_form' => 'catalog_item.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('catalog_item', '\d+')
      ->setRequirement('_entity_access', 'catalog_item.delete')
      ->setOption('_catalog_item_operation_route', TRUE);
    $route_collection->add('entity.catalog_item.delete_form', $route);

    $route = (new Route('/catalog-item/{catalog_item}/edit'))
      ->setDefault('_entity_form', 'catalog_item.edit')
      ->setRequirement('_entity_access', 'catalog_item.update')
      ->setRequirement('catalog_item', '\d+')
      ->setOption('_catalog_item_operation_route', TRUE);
    $route_collection->add('entity.catalog_item.edit_form', $route);

    return $route_collection;
  }

}
