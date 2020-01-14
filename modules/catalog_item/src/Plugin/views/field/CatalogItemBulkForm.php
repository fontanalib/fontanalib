<?php

namespace Drupal\catalog_item\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a catalog_item operations bulk form element.
 *
 * @ViewsField("catalog_item_bulk_form")
 */
class CatalogItemBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }

}
