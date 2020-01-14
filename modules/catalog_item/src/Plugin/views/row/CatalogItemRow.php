<?php

namespace Drupal\catalog_item\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a catalog_item_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:catalog_item",
 * )
 */
class CatalogItemRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode']['default'] = 'teaser';

    return $options;
  }

}
