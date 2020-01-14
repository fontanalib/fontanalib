<?php

/**
 * @file
 * Content administration and module settings user interface.
 */

use Drupal\Core\Link;
use Drupal\catalog_item\CatalogItemInterface;

/**
 * Updates all catalog_items in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $catalog_items
 *   Array of catalog_item nids or catalog_items to update.
 * @param array $updates
 *   Array of key/value pairs with catalog_item field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $catalog_items contains an array of catalog_item IDs to be loaded, FALSE
 *   if it contains fully loaded catalog_items. Defaults to FALSE.
 * @param bool $revisions
 *   (optional) TRUE if $catalog_items contains an array of revision IDs instead of
 *   catalog_item IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 */
function catalog_item_mass_update(array $catalog_items, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of catalog_items.
  if (count($catalog_items) > 10) {
    $batch = [
      'operations' => [
        ['_catalog_item_mass_update_batch_process', [$catalog_items, $updates, $langcode, $load, $revisions]],
      ],
      'finished' => '_catalog_item_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'catalog_item') . '/catalog_item.admin.inc',
    ];
    batch_set($batch);
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('catalog_item');
    if ($load && !$revisions) {
      $catalog_items = $storage->loadMultiple($catalog_items);
    }
    foreach ($catalog_items as $catalog_item) {
      if ($load && $revisions) {
        $catalog_item = $storage->loadRevision($catalog_item);
      }
      _catalog_item_mass_update_helper($catalog_item, $updates, $langcode);
    }
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
}

/**
 * Updates individual catalog_items when fewer than 10 are queued.
 *
 * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
 *   A catalog_item to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\catalog_item\CatalogItemInterface
 *   An updated catalog_item object.
 *
 * @see catalog_item_mass_update()
 */
function _catalog_item_mass_update_helper(CatalogItemInterface $catalog_item, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? [$langcode] : array_keys($catalog_item->getTranslationLanguages());
  // For efficiency manually save the original catalog_item before applying any changes.
  $catalog_item->original = clone $catalog_item;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $catalog_item->getTranslation($langcode)->$name = $value;
    }
  }
  $catalog_item->save();
  return $catalog_item;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for catalog_item_mass_update().
 *
 * @param array $catalog_items
 *   An array of catalog_item IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $catalog_items contains an array of catalog_item IDs to be loaded, FALSE if it
 *   contains fully loaded catalog_items.
 * @param bool $revisions
 *   (optional) TRUE if $catalog_items contains an array of revision IDs instead of
 *   catalog_item IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 * @param array|\ArrayAccess $context
 *   An array of contextual key/values.
 */
function _catalog_item_mass_update_batch_process(array $catalog_items, array $updates, $langcode, $load, $revisions, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($catalog_items);
    $context['sandbox']['catalog_items'] = $catalog_items;
  }

  // Process catalog_items by groups of 5.
  $storage = \Drupal::entityTypeManager()->getStorage('catalog_item');
  $count = min(5, count($context['sandbox']['catalog_items']));
  for ($i = 1; $i <= $count; $i++) {
    // For each nid, load the catalog_item, reset the values, and save it.
    $catalog_item = array_shift($context['sandbox']['catalog_items']);
    if ($load) {
      $catalog_item = $revisions ?
        $storage->loadRevision($catalog_item) : $storage->load($catalog_item);
    }
    $catalog_item = _catalog_item_mass_update_helper($catalog_item, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = Link::fromTextAndUrl($catalog_item->label(), $catalog_item->toUrl())->toString();

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Implements callback_batch_finished().
 *
 * Reports the 'finished' status of batch operation for catalog_item_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to catalog_items updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _catalog_item_mass_update_batch_process()
 */
function _catalog_item_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
  else {
    \Drupal::messenger()->addError(t('An error occurred and processing did not complete.'));
    $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = [
      '#theme' => 'item_list',
      '#items' => $results,
    ];
    $message .= \Drupal::service('renderer')->render($item_list);
    \Drupal::messenger()->addStatus($message);
  }
}
