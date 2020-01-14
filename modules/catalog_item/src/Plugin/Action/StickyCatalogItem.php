<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Makes a catalog_item sticky.
 *
 * @Action(
 *   id = "catalog_item_make_sticky_action",
 *   label = @Translation("Make selected content sticky"),
 *   type = "catalog_item"
 * )
 */
class StickyCatalogItem extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['sticky' => CatalogItemInterface::STICKY];
  }

}
