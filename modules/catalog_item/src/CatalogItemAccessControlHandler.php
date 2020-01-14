<?php

namespace Drupal\catalog_item;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the catalog_item entity type.
 *
 * @see \Drupal\catalog_item\Entity\CatalogItem
 * @ingroup catalog_item_access
 */
class CatalogItemAccessControlHandler extends EntityAccessControlHandler implements CatalogItemAccessControlHandlerInterface, EntityHandlerInterface {

  /**
   * The catalog_item grant storage.
   *
   * @var \Drupal\catalog_item\CatalogItemGrantDatabaseStorageInterface
   */
  protected $grantStorage;

  /**
   * Constructs a CatalogItemAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\catalog_item\CatalogItemGrantDatabaseStorageInterface $grant_storage
   *   The catalog_item grant storage.
   */
  public function __construct(EntityTypeInterface $entity_type, CatalogItemGrantDatabaseStorageInterface $grant_storage) {
    parent::__construct($entity_type);
    $this->grantStorage = $grant_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('catalog_item.grant_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass catalog access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    if (!$account->hasPermission('access catalog')) {
      $result = AccessResult::forbidden("The 'access catalog' permission is required.")->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('bypass catalog access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    if (!$account->hasPermission('access catalog')) {
      $result = AccessResult::forbidden("The 'access catalog' permission is required.")->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $catalog_item, $operation, AccountInterface $account) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */

    // Fetch information from the catalog_item object if possible.
    $status = $catalog_item->isPublished();
    $uid = $catalog_item->getOwnerId();

    // Check if authors can view their own unpublished catalog_items.
    if ($operation === 'view' && !$status && $account->hasPermission('view own unpublished catalog items') && $account->isAuthenticated() && $account->id() == $uid) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($catalog_item);
    }

    // Evaluate catalog_item grants.
    return $this->grantStorage->access($catalog_item, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' catalog items'))->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Only users with the administer catalog items permission can edit administrative
    // fields.
    $administrative_fields = ['uid', 'status', 'created', 'promote', 'sticky'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $administrative_fields, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'administer catalog items');
    }

    // No user can change read only fields.
    $read_only_fields = ['revision_timestamp', 'revision_uid'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $read_only_fields, TRUE)) {
      return AccessResult::forbidden();
    }

    // Users have access to the revision_log field either if they have
    // administrative permissions or if the new revision option is enabled.
    if ($operation == 'edit' && $field_definition->getName() == 'revision_log') {
      if ($account->hasPermission('administer catalog items')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      /**
       * Check catalog vs. type @HERE
       */
      return AccessResult::allowedIf($items->getEntity()->catalog->entity->shouldCreateNewRevision())->cachePerPermissions();
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function acquireGrants(CatalogItemInterface $catalog_item) {
    $grants = $this->moduleHandler->invokeAll('catalog_item_access_records', [$catalog_item]);
    // Let modules alter the grants.
    $this->moduleHandler->alter('catalog_item_access_records', $grants, $catalog_item);
    // If no grants are set and the catalog_item is published, then use the default grant.
    if (empty($grants) && $catalog_item->isPublished()) {
      $grants[] = ['realm' => 'all', 'gid' => 0, 'grant_view' => 1, 'grant_update' => 0, 'grant_delete' => 0];
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function writeGrants(CatalogItemInterface $catalog_item, $delete = TRUE) {
    $grants = $this->acquireGrants($catalog_item);
    $this->grantStorage->write($catalog_item, $grants, NULL, $delete);
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefaultGrant() {
    $this->grantStorage->writeDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteGrants() {
    $this->grantStorage->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function countGrants() {
    return $this->grantStorage->count();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAllGrants(AccountInterface $account) {
    return $this->grantStorage->checkAll($account);
  }

}
