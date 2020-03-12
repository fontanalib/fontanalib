<?php

namespace Drupal\catalog_importer\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a creator field mapper.
 *
 * @FeedsTarget(
 *   id = "identifier",
 *   field_types = {"catalog_item_identifier"}
 * )
 */
class Identifier extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('id')
      ->addProperty('type')
      ->addProperty('cover_image');;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['type'] = trim($values['type'], " \t\n\r\0\x0B-./\\");
    if(strtolower($values['type']) == 'isbn'){
      $isbn = preg_replace('/\D/', '', $values['id']);
      if (strlen($isbn) === 9) {
        $isbn = $isbn . "X";
      }
      $values['id'] = $isbn;
    }
    switch(strtolower($values['type'])){
      case 'isbn':
      case 'upc':
      case 'image url':
        $values['cover_image'] = 1; break;
      default: $values['cover_image'] = 0;
    }
  }

}
