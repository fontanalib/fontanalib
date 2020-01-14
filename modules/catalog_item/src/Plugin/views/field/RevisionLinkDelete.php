<?php

namespace Drupal\catalog_item\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a catalog_item revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("catalog_item_revision_link_delete")
 */
class RevisionLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $this->getEntity($row);
    return Url::fromRoute('catalog_item.revision_delete_confirm', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $catalog_item->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete');
  }

}
