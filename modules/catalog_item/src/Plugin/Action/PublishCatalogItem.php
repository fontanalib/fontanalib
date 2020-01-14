<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\PublishAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Publishes a catalog_item.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\PublishAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\PublishAction
 * @see https://www.drupal.org/node/2919303
 *
 * @Action(
 *   id = "catalog_item_publish_action",
 *   label = @Translation("Publish selected content"),
 *   type = "catalog_item"
 * )
 */
class PublishCatalogItem extends PublishAction {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
  }

}
