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
 *   id = "creator",
 *   label = @Translation("Creator field"),
 *   default_formatter = "creator_default",
 *   category = @Translation("Catalog Item"),
 *   default_widget = "creator_default",
 * )
 */
class Creator extends FieldItemBase implements FieldItemInterface {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      // columns contains the values that the field will store
      'columns' => array(
        // List the values that the field will save.
        'name' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ),
        'role' => array(
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ),
      ),
    );
  }
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(t('Creator Name'));
    $properties['role'] = DataDefinition::create('string')
      ->setLabel(t('Creator Role'));

    return $properties;
  }
  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('name')->getValue();
    return $value === NULL || $value === '';
  }

}