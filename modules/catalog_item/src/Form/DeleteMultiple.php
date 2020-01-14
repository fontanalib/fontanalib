<?php

namespace Drupal\catalog_item\Form;

use Drupal\Core\Entity\Form\DeleteMultipleForm as EntityDeleteMultipleForm;
use Drupal\Core\Url;

/**
 * Provides a catalog_item deletion confirmation form.
 *
 * @internal
 */
class DeleteMultiple extends EntityDeleteMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('catalog_item.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletedMessage($count) {
    return $this->formatPlural($count, 'Deleted @count catalog item.', 'Deleted @count catalog items.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count catalog item has not been deleted because you do not have the necessary permissions.", "@count catalog items have not been deleted because you do not have the necessary permissions.");
  }

}
