<?php

namespace Drupal\catalog_item\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemInterface;


/**
 * Provides a field type of creator.
 * 
 * @FieldType(
 *   id = "catalog_item_identifier",
 *   label = @Translation("Catalog Identifier field"),
 *   category = @Translation("Catalog Item"),
 *   default_formatter = "catalog_item_identifier_default",
 *   default_widget = "catalog_item_identifier_default",
 * )
 */
class CatalogItemIdentifier extends FieldItemBase implements FieldItemInterface {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      // columns contains the values that the field will store
      'columns' => array(
        // List the values that the field will save.
        'id' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ),
        'type' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ),
        'cover_image' => array(
          'type' => 'int',
          'size' => 'tiny',
        ),
      ),
    );
  }
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['id'] = DataDefinition::create('string')
      ->setLabel(t('Identifier ID'));
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Identifier Type'));
    $properties['cover_image'] = DataDefinition::create('boolean')
    ->setLabel(t('Use as cover image (boolean)'))
    ->setRequired(TRUE);

    return $properties;
  }
  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('id')->getValue();
    return $value === NULL || $value === '';
  }

}