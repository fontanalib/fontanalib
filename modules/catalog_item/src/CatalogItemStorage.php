<?php

namespace Drupal\catalog_item;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the storage handler class for catalog_items.
 *
 * This extends the base storage class, adding required special handling for
 * catalog_item entities.
 */
class CatalogItemStorage extends SqlContentEntityStorage implements CatalogItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CatalogItemInterface $catalog_item) {
    return $this->database->query(
      'SELECT vid FROM {' . $this->getRevisionTable() . '} WHERE nid=:nid ORDER BY vid',
      [':nid' => $catalog_item->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {' . $this->getRevisionDataTable() . '} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(CatalogItemInterface $catalog_item) {
    return $this->database->query('SELECT COUNT(*) FROM {' . $this->getRevisionDataTable() . '} WHERE nid = :nid AND default_langcode = 1', [':nid' => $catalog_item->id()])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function changeCatalog($old_catalog, $new_catalog) {
    return $this->database->update($this->getBaseTable())
      ->fields(['catalog' => $new_catalog])
      ->condition('catalog', $old_catalog)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update($this->getRevisionTable())
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
