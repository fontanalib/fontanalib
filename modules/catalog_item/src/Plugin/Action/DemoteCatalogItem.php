<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Demotes a catalog_item.
 *
 * @Action(
 *   id = "catalog_item_unpromote_action",
 *   label = @Translation("Demote selected catalog items from front page"),
 *   type = "catalog_item"
 * )
 */
class DemoteCatalogItem extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['promote' => CatalogItemInterface::NOT_PROMOTED];
  }

}
