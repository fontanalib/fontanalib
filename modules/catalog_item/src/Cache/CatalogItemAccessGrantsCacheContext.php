<?php

namespace Drupal\catalog_item\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;

/**
 * Defines the catalog_item access view cache context service.
 *
 * Cache context ID: 'user.catalog_item_grants' (to vary by all operations' grants).
 * Calculated cache context ID: 'user.catalog_item_grants:%operation', e.g.
 * 'user.catalog_item_grants:view' (to vary by the view operation's grants).
 *
 * This allows for catalog_item access grants-sensitive caching when listing catalog_items.
 *
 * @see catalog_item_query_catalog_item_access_alter()
 * @ingroup catalog_item_access
 */
class CatalogItemAccessGrantsCacheContext extends UserCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Catalog access view grants");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($operation = NULL) {
    // If the current user either can bypass catalog_item access then we don't need to
    // determine the exact catalog_item grants for the current user.
    if ($this->user->hasPermission('bypass catalog access')) {
      return 'all';
    }

    // When no specific operation is specified, check the grants for all three
    // possible operations.
    if ($operation === NULL) {
      $result = [];
      foreach (['view', 'update', 'delete'] as $op) {
        $result[] = $this->checkCatalogItemGrants($op);
      }
      return implode('-', $result);
    }
    else {
      return $this->checkCatalogItemGrants($operation);
    }
  }

  /**
   * Checks the catalog_item grants for the given operation.
   *
   * @param string $operation
   *   The operation to check the catalog_item grants for.
   *
   * @return string
   *   The string representation of the cache context.
   */
  protected function checkCatalogItemGrants($operation) {
    // When checking the grants for the 'view' operation and the current user
    // has a global view grant (i.e. a view grant for catalog_item ID 0) — note that
    // this is automatically the case if no catalog_item access modules exist (no
    // hook_catalog_item_grants() implementations) then we don't need to determine the
    // exact catalog_item view grants for the current user.
    if ($operation === 'view' && catalog_item_access_view_all_catalog_items($this->user)) {
      return 'view.all';
    }

    $grants = catalog_item_access_grants($operation, $this->user);
    $grants_context_parts = [];
    foreach ($grants as $realm => $gids) {
      $grants_context_parts[] = $realm . ':' . implode(',', $gids);
    }
    return $operation . '.' . implode(';', $grants_context_parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($operation = NULL) {
    $cacheable_metadata = new CacheableMetadata();

    if (!\Drupal::moduleHandler()->getImplementations('catalog_item_grants')) {
      return $cacheable_metadata;
    }

    // The catalog_item grants may change if the user is updated. (The max-age is set to
    // zero below, but sites may override this cache context, and change it to a
    // non-zero value. In such cases, this cache tag is needed for correctness.)
    $cacheable_metadata->setCacheTags(['user:' . $this->user->id()]);

    // If the site is using catalog_item grants, this cache context can not be
    // optimized.
    return $cacheable_metadata->setCacheMaxAge(0);
  }

}
