<?php

namespace Drupal\catalog_item\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\catalog_item\CatalogItemInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for catalog_item revisions.
 *
 * @ingroup catalog_item_access
 */
class CatalogItemRevisionAccessCheck implements AccessInterface {

  /**
   * The catalog_item storage.
   *
   * @var \Drupal\catalog_item\CatalogItemStorageInterface
   */
  protected $catalog_itemStorage;

  /**
   * The catalog_item access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $catalog_itemAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new CatalogItemRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->catalog_itemStorage = $entity_type_manager->getStorage('catalog_item');
    $this->catalog_itemAccess = $entity_type_manager->getAccessControlHandler('catalog_item');
  }

  /**
   * Checks routing access for the catalog_item revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $catalog_item_revision
   *   (optional) The catalog_item revision ID. If not specified, but $catalog_item is, access
   *   is checked for that object's revision.
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   (optional) A catalog_item object. Used for checking access to a catalog_item's default
   *   revision when $catalog_item_revision is unspecified. Ignored when $catalog_item_revision
   *   is specified. If neither $catalog_item_revision nor $catalog_item are specified, then
   *   access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $catalog_item_revision = NULL, CatalogItemInterface $catalog_item = NULL) {
    if ($catalog_item_revision) {
      $catalog_item = $this->catalog_itemStorage->loadRevision($catalog_item_revision);
    }
    $operation = $route->getRequirement('_access_catalog_item_revision');
    return AccessResult::allowedIf($catalog_item && $this->checkAccess($catalog_item, $account, $operation))->cachePerPermissions()->addCacheableDependency($catalog_item);
  }

  /**
   * Checks catalog_item revision access.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The catalog_item to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view.'
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(CatalogItemInterface $catalog_item, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view all catalog revisions',
      'update' => 'revert all catalog revisions',
      'delete' => 'delete all catalog revisionss',
    ];
    $bundle = $catalog_item->bundle();
    $type_map = [
      'view' => "view $bundle revisions",
      'update' => "revert $bundle revisions",
      'delete' => "delete $bundle revisions",
    ];

    if (!$catalog_item || !isset($map[$op]) || !isset($type_map[$op])) {
      // If there was no catalog_item to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $catalog_item->language()->getId();
    $cid = $catalog_item->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$op]) && !$account->hasPermission($type_map[$op]) && !$account->hasPermission('administer catalog items')) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }
      // If the revisions checkbox is selected for the content type, display the
      // revisions tab.
      $bundle_entity_type = $catalog_item->getEntityType()->getBundleEntityType();
      $bundle_entity = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle);
      if ($bundle_entity->shouldCreateNewRevision() && $op === 'view') {
        $this->access[$cid] = TRUE;
      }
      else {
        // There should be at least two revisions. If the vid of the given catalog_item
        // and the vid of the default revision differ, then we already have two
        // different revisions so there is no need for a separate database
        // check. Also, if you try to revert to or delete the default revision,
        // that's not good.
        if ($catalog_item->isDefaultRevision() && ($this->catalog_itemStorage->countDefaultLanguageRevisions($catalog_item) == 1 || $op === 'update' || $op === 'delete')) {
          $this->access[$cid] = FALSE;
        }
        elseif ($account->hasPermission('administer catalog items')) {
          $this->access[$cid] = TRUE;
        }
        else {
          // First check the access to the default revision and finally, if the
          // catalog_item passed in is not the default revision then check access to
          // that, too.
          $this->access[$cid] = $this->catalog_itemAccess->access($this->catalog_itemStorage->load($catalog_item->id()), $op, $account) && ($catalog_item->isDefaultRevision() || $this->catalog_itemAccess->access($catalog_item, $op, $account));
        }
      }
    }

    return $this->access[$cid];
  }

}
