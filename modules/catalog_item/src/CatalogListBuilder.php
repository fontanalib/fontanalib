<?php

namespace Drupal\catalog_item;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of catalog_item type entities.
 *
 * @see \Drupal\catalog_item\Entity\Catalog
 */
class CatalogListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Name');
    $header['description'] = [
      'data' => t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No catalogs available. <a href=":link">Add catalog</a>.', [
        ':link' => Url::fromRoute('catalog_item.catalog_add')->toString(),
      ]);
    return $build;
  }

}
