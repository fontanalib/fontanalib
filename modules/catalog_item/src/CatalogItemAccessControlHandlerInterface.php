<?php

namespace Drupal\catalog_item;

use Drupal\Core\Session\AccountInterface;

/**
 * CatalogItem specific entity access control methods.
 *
 * @ingroup catalog_item_access
 */
interface CatalogItemAccessControlHandlerInterface {

  /**
   * Gets the list of catalog_item access grants.
   *
   * This function is called to check the access grants for a catalog_item. It collects
   * all catalog_item access grants for the catalog_item from hook_catalog_item_access_records()
   * implementations, allows these grants to be altered via
   * hook_catalog_item_access_records_alter() implementations, and returns the grants to
   * the caller.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The $catalog_item to acquire grants for.
   *
   * @return array
   *   The access rules for the catalog_item.
   */
  public function acquireGrants(CatalogItemInterface $catalog_item);

  /**
   * Writes a list of grants to the database, deleting any previously saved ones.
   *
   * Modules that use catalog_item access can use this function when doing mass updates
   * due to widespread permission changes.
   *
   * Note: Don't call this function directly from a contributed module. Call
   * \Drupal\catalog_item\CatalogItemAccessControlHandlerInterface::acquireGrants() instead.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The catalog_item whose grants are being written.
   * @param $delete
   *   (optional) If false, does not delete records. This is only for optimization
   *   purposes, and assumes the caller has already performed a mass delete of
   *   some form. Defaults to TRUE.
   *
   * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0.
   *   Use \Drupal\catalog_item\CatalogItemAccessControlHandlerInterface::acquireGrants().
   */
  public function writeGrants(CatalogItemInterface $catalog_item, $delete = TRUE);

  /**
   * Creates the default catalog_item access grant entry on the grant storage.
   */
  public function writeDefaultGrant();

  /**
   * Deletes all catalog_item access entries.
   */
  public function deleteGrants();

  /**
   * Counts available catalog_item grants.
   *
   * @return int
   *   Returns the amount of catalog_item grants.
   */
  public function countGrants();

  /**
   * Checks all grants for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   *
   * @return int
   *   Status of the access check.
   */
  public function checkAllGrants(AccountInterface $account);

}
