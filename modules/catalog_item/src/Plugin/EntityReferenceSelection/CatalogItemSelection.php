<?php

namespace Drupal\catalog_item\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Provides specific access control for the catalog_item entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:catalog_item",
 *   label = @Translation("Catalog Item selection"),
 *   entity_types = {"catalog_item"},
 *   group = "default",
 *   weight = 1
 * )
 */
class CatalogItemSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'catalog_item_access' tag is sadly insufficient for catalog_items: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    if (!$this->currentUser->hasPermission('bypass catalog access') && !count($this->moduleHandler->getImplementations('catalog_item_grants'))) {
      $query->condition('status', CatalogItemInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $catalog_item = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable catalog_item, it needs to published.
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item->setPublished();

    return $catalog_item;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('bypass catalog access') && !count($this->moduleHandler->getImplementations('catalog_item_grants'))) {
      $entities = array_filter($entities, function ($catalog_item) {
        /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
        return $catalog_item->isPublished();
      });
    }
    return $entities;
  }

}
