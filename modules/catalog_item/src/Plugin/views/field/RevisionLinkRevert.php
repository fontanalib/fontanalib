<?php

namespace Drupal\catalog_item\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to revert a catalog_item to a revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("catalog_item_revision_link_revert")
 */
class RevisionLinkRevert extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $this->getEntity($row);
    return Url::fromRoute('catalog_item.revision_revert_confirm', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $catalog_item->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Revert');
  }

}
