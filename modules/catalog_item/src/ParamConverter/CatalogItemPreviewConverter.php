<?php

namespace Drupal\catalog_item\ParamConverter;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for a catalog_item entity in preview.
 */
class CatalogItemPreviewConverter implements ParamConverterInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new CatalogItemPreviewConverter.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $store = $this->tempStoreFactory->get('catalog_item_preview');
    if ($form_state = $store->get($value)) {
      return $form_state->getFormObject()->getEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'catalog_item_preview') {
      return TRUE;
    }
    return FALSE;
  }

}
