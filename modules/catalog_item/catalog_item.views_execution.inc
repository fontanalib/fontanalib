<?php

/**
 * @file
 * Provide views runtime hooks for catalog_item.module.
 */

use Drupal\user\RoleInterface;
use Drupal\views\ViewExecutable;
use Drupal\user\Entity\Role;

/**
 * Implements hook_views_query_substitutions().
 */
function catalog_item_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();
  return [
    '***ADMINISTER_CATALOG_ITEMS***' => intval($account->hasPermission('administer catalog items')),
    '***VIEW_OWN_UNPUBLISHED_CATALOG_ITEMS***' => intval($account->hasPermission('view own unpublished catalog items')),
    '***BYPASS_CATALOG_ACCESS***' => intval($account->hasPermission('bypass catalog access')),
  ];
}

/**
 * Implements hook_views_analyze().
 */
function catalog_item_views_analyze(ViewExecutable $view) {
  $ret = [];
  // Check for something other than the default display:
  if ($view->storage->get('base_table') == 'catalog_item') {
    foreach ($view->displayHandlers as $display) {
      if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
        // check for no access control
        $access = $display->getOption('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
          $anonymous_has_access = $anonymous_role && $anonymous_role->hasPermission('access catalog');
          $authenticated_role = Role::load(RoleInterface::AUTHENTICATED_ID);
          $authenticated_has_access = $authenticated_role && $authenticated_role->hasPermission('access catalog');
          if (!$anonymous_has_access || !$authenticated_has_access) {
            $ret[] = Analyzer::formatMessage(t('Some roles lack permission to access catalog, but display %display has no access control.', ['%display' => $display->display['display_title']]), 'warning');
          }
          $filters = $display->getOption('filters');
          foreach ($filters as $filter) {
            if ($filter['table'] == 'catalog_item' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
              continue 2;
            }
          }
          $ret[] = Analyzer::formatMessage(t('Display %display has no access control but does not contain a filter for published catalog_items.', ['%display' => $display->display['display_title']]), 'warning');
        }
      }
    }
  }
  foreach ($view->displayHandlers as $display) {
    if ($display->getPluginId() == 'page') {
      if ($display->getOption('path') == 'catalog-item/%') {
        $ret[] = Analyzer::formatMessage(t('Display %display has set catalog-item/% as path. This will not produce what you want. If you want to have multiple versions of the catalog_item view, use panels.', ['%display' => $display->display['display_title']]), 'warning');
      }
    }
  }

  return $ret;
}
