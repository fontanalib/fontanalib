<?php

/**
 * @file
 * Content administration and module settings user interface.
 */

/**
 * Updates all entities in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $entities
 *   Array of entity ids or entities to update.
 * @param array $updates
 *   Array of key/value pairs with entity field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $entities contains an array of entity IDs to be loaded, FALSE
 *   if it contains fully loaded entities. Defaults to FALSE.
 * @param bool $revisions
 *   (optional) TRUE if $entities contains an array of revision IDs instead of
 *   entity IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 */
function entity_mass_update(string $entity_type, array $entities, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of entities.
  if (count($entities) > 10) {
    $batch = [
      'operations' => [
        ['_entity_mass_update_batch_process', [$entity_type, $entities, $updates, $langcode, $load, $revisions]],
      ],
      'finished' => '_entity_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'fontanalib') . '/fontanalib.admin.inc',
    ];
    batch_set($batch);
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('entity');
    if ($load && !$revisions) {
      $entities = $storage->loadMultiple($entities);
    }
    foreach ($entities as $entity) {
      if ($load && $revisions) {
        $entity = $storage->loadRevision($entity);
      }
      _entity_mass_update_helper($entity_type, $entity, $updates, $langcode);
    }
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
}

/**
 * Updates individual entities when fewer than 10 are queued.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   A entity to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   An updated entity object.
 *
 * @see entity_mass_update()
 */
function _entity_mass_update_helper(string $entity_type, EntityInterface $entity, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? [$langcode] : array_keys($entity->getTranslationLanguages());
  // For efficiency manually save the original entity before applying any changes.
  $entity->original = clone $entity;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $entity->getTranslation($langcode)->$name = $value;
    }
  }
  $entity->save();
  return $entity;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for entity_mass_update().
 *
 * @param array $entities
 *   An array of entity IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $entities contains an array of entity IDs to be loaded, FALSE if it
 *   contains fully loaded entities.
 * @param bool $revisions
 *   (optional) TRUE if $entities contains an array of revision IDs instead of
 *   entity IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 * @param array|\ArrayAccess $context
 *   An array of contextual key/values.
 */
function _entity_mass_update_batch_process(string $entity_type, array $entities, array $updates, $langcode, $load, $revisions, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($entities);
    $context['sandbox'][$entity_type] = $entities;
  }

  // Process entities by groups of 5.
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $count = min(5, count($context['sandbox'][$entity_type]));
  for ($i = 1; $i <= $count; $i++) {
    // For each id, load the entity, reset the values, and save it.
    $entity = array_shift($context['sandbox'][$entity_type]);
    if ($load) {
      $entity = $revisions ?
        $storage->loadRevision($entity) : $storage->load($entity);
    }
    $entity = _entity_mass_update_helper($entity_type, $entity, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = \Drupal\Core\Link::fromTextAndUrl($entity->label(), $entity->toUrl()); //\Drupal::l($entity->label(), $entity->toUrl());

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
 * Reports the 'finished' status of batch operation for entity_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to entities updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _entity_mass_update_batch_process()
 */
function _entity_mass_update_batch_finished($success, $results, $operations) {
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
