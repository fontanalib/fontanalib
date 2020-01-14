<?php

namespace Drupal\catalog_item;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\catalog_item\Entity\Catalog;

/**
 * Provides dynamic permissions for catalog_items of different types.
 */
class CatalogItemPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of catalog_item type permissions.
   *
   * @return array
   *   The catalog_item type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function catalogPermissions() {
    $perms = [];
    // Generate catalog_item permissions for all catalog types.
    foreach (Catalog::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of catalog_item permissions for a given catalog type.
   *
   * @param \Drupal\catalog_item\Entity\Catalog $type
   *   The catalog type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(Catalog $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id catalog items" => [
        'title' => $this->t('%type_name: Create new catalog items', $type_params),
      ],
      "edit own $type_id catalog items" => [
        'title' => $this->t('%type_name: Edit own catalog items', $type_params),
      ],
      "edit any $type_id catalog items" => [
        'title' => $this->t('%type_name: Edit any catalog items', $type_params),
      ],
      "delete own $type_id catalog items" => [
        'title' => $this->t('%type_name: Delete own catalog items', $type_params),
      ],
      "delete any $type_id catalog items" => [
        'title' => $this->t('%type_name: Delete any catalog items', $type_params),
      ],
      "view $type_id catalog revisions" => [
        'title' => $this->t('%type_name: View revisions', $type_params),
        'description' => t('To view a revision, you also need permission to view the catalog item.'),
      ],
      "revert $type_id catalog revisions" => [
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('To revert a revision, you also need permission to edit the catalog item.'),
      ],
      "delete $type_id catalog revisions" => [
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('To delete a revision, you also need permission to delete the catalog item.'),
      ],
    ];
  }

}
