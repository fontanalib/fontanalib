<?php

namespace Drupal\catalog_item;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for catalog_item entity storage classes.
 */
interface CatalogItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of catalog_item revision IDs for a specific catalog_item.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The catalog_item entity.
   *
   * @return int[]
   *   CatalogItem revision IDs (in ascending order).
   */
  public function revisionIds(CatalogItemInterface $catalog_item);

  /**
   * Gets a list of revision IDs having a given user as catalog_item author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   CatalogItem revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The catalog_item entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CatalogItemInterface $catalog_item);

  /**
   * Updates all catalog_items of one type to be of another type.
   *
   * @param string $old_catalog
   *   The current catalog_item type of the catalog_items.
   * @param string $new_catalog
   *   The new catalog_item type of the catalog_items.
   *
   * @return int
   *   The number of catalog_items whose catalog_item type field was modified.
   */
  public function changeCatalog($old_catalog, $new_catalog);

  /**
   * Unsets the language for all catalog_items with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);
}
