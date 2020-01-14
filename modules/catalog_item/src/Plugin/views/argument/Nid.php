<?php

namespace Drupal\catalog_item\Plugin\views\argument;

use Drupal\catalog_item\CatalogItemStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a catalog_item id.
 *
 * @ViewsArgument("catalog_item_nid")
 */
class Nid extends NumericArgument {

  /**
   * The catalog_item storage.
   *
   * @var \Drupal\catalog_item\CatalogItemStorageInterface
   */
  protected $catalog_itemStorage;

  /**
   * Constructs the Nid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\catalog_item\CatalogItemStorageInterface $catalog_item_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CatalogItemStorageInterface $catalog_item_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->catalog_itemStorage = $catalog_item_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('catalog_item')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the catalog_item.
   */
  public function titleQuery() {
    $titles = [];

    $catalog_items = $this->catalog_itemStorage->loadMultiple($this->value);
    foreach ($catalog_items as $catalog_item) {
      $titles[] = $catalog_item->label();
    }
    return $titles;
  }

}
