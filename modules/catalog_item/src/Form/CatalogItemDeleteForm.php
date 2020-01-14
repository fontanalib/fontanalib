<?php

namespace Drupal\catalog_item\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a catalog_item.
 *
 * @internal
 */
class CatalogItemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\catalog_item\CatalogItemInterface $entity */
    $entity = $this->getEntity();

    $catalog_storage = $this->entityTypeManager->getStorage('catalog');
    $catalog = $catalog_storage->load($entity->bundle())->label();

    if (!$entity->isDefaultTranslation()) {
      return $this->t('@language translation of the @type %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => $catalog,
        '%label' => $entity->label(),
      ]);
    }

    return $this->t('The @type item %title has been deleted.', [
      '@type' => $catalog,
      '%title' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    /** @var \Drupal\catalog_item\CatalogItemInterface $entity */
    $entity = $this->getEntity();
    $this->logger('catalog')->notice('@type item: deleted %title.', ['@type' => $entity->getCatalog(), '%title' => $entity->label()]);
  }

}
