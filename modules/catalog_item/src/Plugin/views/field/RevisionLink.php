<?php

namespace Drupal\catalog_item\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a catalog_item revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("catalog_item_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $this->getEntity($row);
    // Current revision uses the catalog_item view path.
    return !$catalog_item->isDefaultRevision() ?
      Url::fromRoute('entity.catalog_item.revision', ['catalog_item' => $catalog_item->id(), 'catalog_item_revision' => $catalog_item->getRevisionId()]) :
      $catalog_item->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $this->getEntity($row);
    if (!$catalog_item->getRevisionid()) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View');
  }

}
