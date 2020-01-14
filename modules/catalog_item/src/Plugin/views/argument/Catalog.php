<?php

namespace Drupal\catalog_item\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a catalog_item type.
 *
 * @ViewsArgument("catalog")
 */
class Catalog extends StringArgument {

  /**
   * catalog storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $catalogStorage;

  /**
   * Constructs a new catalog_item Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $catalog_storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $catalog_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->catalogStorage = $catalog_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager->getStorage('catalog')
    );
  }

  /**
   * Override the behavior of summaryName(). Get the user friendly version
   * of the catalog_item type.
   */
  public function summaryName($data) {
    return $this->catalog($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version of the
   * catalog_item type.
   */
  public function title() {
    return $this->catalog($this->argument);
  }

  public function catalog($type_name) {
    $type = $this->catalogStorage->load($type_name);
    $output = $type ? $type->label() : $this->t('Unknown catalog');
    return $output;
  }

}
