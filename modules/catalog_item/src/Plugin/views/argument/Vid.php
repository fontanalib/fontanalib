<?php

namespace Drupal\catalog_item\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\catalog_item\CatalogItemStorageInterface;

/**
 * Argument handler to accept a catalog_item revision id.
 *
 * @ViewsArgument("catalog_item_vid")
 */
class Vid extends NumericArgument {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The catalog_item storage.
   *
   * @var \Drupal\catalog_item\CatalogItemStorageInterface
   */
  protected $catalog_itemStorage;

  /**
   * Constructs a \Drupal\catalog_item\Plugin\views\argument\Vid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\catalog_item\CatalogItemStorageInterface $catalog_item_storage
   *   The catalog_item storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, CatalogItemStorageInterface $catalog_item_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
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
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('catalog_item')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = [];

    $results = $this->database->query('SELECT cr.vid, cr.nid, cr.title FROM {catalog_item_revision} cr WHERE cr.vid IN ( :vids[] )', [':vids[]' => $this->value])->fetchAllAssoc('vid', PDO::FETCH_ASSOC);
    $nids = [];
    foreach ($results as $result) {
      $nids[] = $result['nid'];
    }

    $catalog_items = $this->catalog_itemStorage->loadMultiple(array_unique($nids));

    foreach ($results as $result) {
      $catalog_items[$result['nid']]->set('title', $result['title']);
      $titles[] = $catalog_items[$result['nid']]->label();
    }

    return $titles;
  }

}
