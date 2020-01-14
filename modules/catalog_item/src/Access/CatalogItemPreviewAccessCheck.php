<?php

namespace Drupal\catalog_item\Access;

use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Determines access to catalog_item previews.
 *
 * @ingroup catalog_item_access
 */
class CatalogItemPreviewAccessCheck implements AccessInterface {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['entityManager' => 'entity.manager'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the catalog_item preview page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item_preview
   *   The catalog_item that is being previewed.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, CatalogItemInterface $catalog_item_preview) {
    if ($catalog_item_preview->isNew()) {
      $access_controller = $this->entityTypeManager->getAccessControlHandler('catalog_item');
      return $access_controller->createAccess($catalog_item_preview->bundle(), $account, [], TRUE);
    }
    else {
      return $catalog_item_preview->access('update', $account, TRUE);
    }
  }

}
