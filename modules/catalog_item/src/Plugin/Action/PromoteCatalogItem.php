<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Promotes a catalog_item.
 *
 * @Action(
 *   id = "catalog_item_promote_action",
 *   label = @Translation("Promote selected catalog items to front page"),
 *   type = "catalog_item"
 * )
 */
class PromoteCatalogItem extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['promote' => CatalogItemInterface::PROMOTED];
  }

}
