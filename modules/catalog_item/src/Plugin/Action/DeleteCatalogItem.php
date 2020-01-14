<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\DeleteAction;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Redirects to a catalog_item deletion form.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0.
 *   Use \Drupal\Core\Action\Plugin\Action\DeleteAction instead.
 *
 * @see \Drupal\Core\Action\Plugin\Action\DeleteAction
 * @see https://www.drupal.org/node/2934349
 *
 * @Action(
 *   id = "catalog_item_delete_action",
 *   label = @Translation("Delete content")
 * )
 */
class DeleteCatalogItem extends DeleteAction {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $temp_store_factory, $current_user);
  }

}
