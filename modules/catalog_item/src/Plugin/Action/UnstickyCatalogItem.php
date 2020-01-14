<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Makes a catalog_item not sticky.
 *
 * @Action(
 *   id = "catalog_item_make_unsticky_action",
 *   label = @Translation("Make selected content not sticky"),
 *   type = "catalog_item"
 * )
 */
class UnstickyCatalogItem extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['sticky' => CatalogItemInterface::NOT_STICKY];
  }

}
