<?php

// namespace Drupal\catalog_item\Routing;

// use Drupal\Core\Routing\RouteSubscriberBase;
// use Symfony\Component\Routing\RouteCollection;

// /**
//  * Listens to the dynamic route events.
//  */
// class RouteSubscriber extends RouteSubscriberBase {

//   /**
//    * {@inheritdoc}
//    */
//   protected function alterRoutes(RouteCollection $collection) {
//     // As catalog_items are the primary type of content, the catalog_item listing should be
//     // easily available. In order to do that, override admin/content to show
//     // a catalog_item listing instead of the path's child links.
//     $route = $collection->get('system.admin_content');
//     if ($route) {
//       $route->setDefaults([
//         '_title' => 'Catalog',
//         '_entity_list' => 'catalog_item',
//       ]);
//       $route->setRequirements([
//         '_permission' => 'access catalog overview',
//       ]);
//     }
//   }

// }
