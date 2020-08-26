<?php

namespace Drupal\fontanalib_catalog\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fontanalib_catalog\CatalogInterface;

/**
 * Defines the Catalog entity.
 *
 * @ConfigEntityType(
 *   id = "catalog",
 *   label = @Translation("Catalog"),
 *   handlers = {
 *     "list_builder" = "Drupal\fontanalib_catalog\Controller\CatalogListBuilder",
 *     "form" = {
 *       "add" = "Drupal\fontanalib_catalog\Form\CatalogForm",
 *       "edit" = "Drupal\fontanalib_catalog\Form\CatalogForm",
 *       "delete" = "Drupal\fontanalib_catalog\Form\CatalogDeleteForm",
 *     }
 *   },
 *   config_prefix = "catalog",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/catalog/{catalog}",
 *     "delete-form" = "/admin/config/system/catalog/{catalog}/delete",
 *   }
 * )
 */
class Catalog extends ConfigEntityBase implements CatalogInterface {

  /**
   * The Catalog ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Catalog label.
   *
   * @var string
   */
  public $label;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.
}