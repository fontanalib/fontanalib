<?php

/**
 * @file
 * Post update functions for Catalog_item.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
* Load all form displays for catalog_items, add status with these settings, save.
*/
function catalog_item_post_update_configure_status_field_widget() {
  $query = \Drupal::entityQuery('entity_form_display')->condition('targetEntityType', 'catalog_item');
  $ids = $query->execute();
  $form_displays = EntityFormDisplay::loadMultiple($ids);

  // Assign status settings for each 'catalog_item' target entity types with 'default'
  // form mode.
  foreach ($form_displays as $id => $form_display) {
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
    $form_display->setComponent('status', [
      'type' => 'boolean_checkbox',
      'settings' => [
        'display_label' => TRUE,
      ],
    ])->save();
  }
}

/**
 * Clear caches due to updated views data.
 */
function catalog_item_post_update_catalog_item_revision_views_data() {
  // Empty post-update hook.
}
