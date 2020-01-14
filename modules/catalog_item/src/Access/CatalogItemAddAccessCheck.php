<?php

namespace Drupal\catalog_item\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\catalog_item\CatalogInterface;

/**
 * Determines access to for catalog_item add pages.
 *
 * @ingroup catalog_item_access
 */
class CatalogItemAddAccessCheck implements AccessInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the catalog_item add page for the catalog_item type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\catalog_item\CatalogInterface $catalog
   *   (optional) The catalog type. If not specified, access is allowed if there
   *   exists at least one catalog type for which the user may create a catalog item.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, CatalogInterface $catalog = NULL) {
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('catalog_item');
    // If checking whether a catalog_item of a particular type may be created.
    if ($account->hasPermission('administer catalogs')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($catalog) {
      return $access_control_handler->createAccess($catalog->id(), $account, [], TRUE);
    }
    // If checking whether a catalog_item of any type may be created.
    foreach ($this->entityTypeManager->getStorage('catalog')->loadMultiple() as $catalog) {
      if (($access = $access_control_handler->createAccess($catalog->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
