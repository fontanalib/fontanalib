<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\UnpublishAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Unpublishes a catalog_item.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\UnpublishAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\UnpublishAction
 * @see https://www.drupal.org/node/2919303
 *
 * @Action(
 *   id = "catalog_item_unpublish_action",
 *   label = @Translation("Unpublish selected catalog items"),
 *   type = "catalog_item"
 * )
 */
class UnpublishCatalogItem extends UnpublishAction {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
  }

}
