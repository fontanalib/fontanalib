<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Action\Plugin\Action\SaveAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an action that can save any entity.
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\SaveAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\SaveAction
 * @see https://www.drupal.org/node/2919303
 *
 * @Action(
 *   id = "catalog_item_save_action",
 *   label = @Translation("Save catalog items"),
 *   type = "catalog_item"
 * )
 */
class SaveCatalogItem extends SaveAction {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $time);
  }

}
