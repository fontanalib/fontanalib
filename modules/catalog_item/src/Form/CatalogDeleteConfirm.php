<?php

namespace Drupal\catalog_item\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for catalog deletion.
 *
 * @internal
 */
class CatalogDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_catalog_items = $this->entityTypeManager->getStorage('catalog_item')->getQuery()
      ->condition('catalog', $this->entity->id())
      ->count()
      ->execute();
    if ($num_catalog_items) {
      $caption = '<p>' . $this->formatPlural($num_catalog_items, '%type catalog is used by 1 catalog item on your site. You can not remove this catalog until you have removed all of the %type catalog items.', '%type catalog is used by @count catalog items on your site. You may not remove %type catalog until you have removed all of the %type catalog items.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
