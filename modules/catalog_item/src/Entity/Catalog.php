<?php

namespace Drupal\catalog_item\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\catalog_item\CatalogInterface;

/**
 * Defines the Catalog type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "catalog",
 *   label = @Translation("Catalog"),
 *   label_collection = @Translation("Catalogs"),
 *   label_singular = @Translation("catalog"),
 *   label_plural = @Translation("catalogs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count catalog",
 *     plural = "@count catalogs",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\catalog_item\CatalogAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\catalog_item\CatalogForm",
 *       "edit" = "Drupal\catalog_item\CatalogForm",
 *       "delete" = "Drupal\catalog_item\Form\CatalogDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\catalog_item\CatalogListBuilder",
 *   },
 *   admin_permission = "administer catalogs",
 *   config_prefix = "catalog",
 *   bundle_of = "catalog_item",
 *   entity_keys = {
 *     "id" = "catalog",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/catalogs/manage/{catalog}",
 *     "delete-form" = "/admin/structure/catalogs/manage/{catalog}/delete",
 *     "collection" = "/admin/structure/catalogs",
 *   },
 *   config_export = {
 *     "name",
 *     "catalog",
 *     "description",
 *     "help",
 *     "new_revision",
 *     "preview_mode",
 *     "display_submitted",
 *   }
 * )
 */
class Catalog extends ConfigEntityBundleBase implements CatalogInterface {

  /**
   * The machine name of this catalog_item type.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  protected $catalog;

  /**
   * The human-readable name of the catalog_item type.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  protected $name;

  /**
   * A brief description of this catalog_item type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a CatalogItem of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this catalog_item type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * The preview mode.
   *
   * @var int
   */
  protected $preview_mode = DRUPAL_OPTIONAL;

  /**
   * Display setting for author and date Submitted by post information.
   *
   * @var bool
   */
  protected $display_submitted = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->catalog;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('catalog_item.catalog.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }


  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function displaySubmitted() {
    return $this->display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplaySubmitted($display_submitted) {
    $this->display_submitted = $display_submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewMode() {
    return $this->preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviewMode($preview_mode) {
    $this->preview_mode = $preview_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = catalog_update_catalog_items($this->getOriginalId(), $this->id());
      if ($update_count) {
        \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural($update_count,
          'Changed the content type of 1 post from %old-type to %type.',
          'Changed the content type of @count posts from %old-type to %type.',
          [
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          ]));
      }
    }
    if ($update) {
      // Clear the cached field definitions as some settings affect the field
      // definitions.
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the catalog_item type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}
