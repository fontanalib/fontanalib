<?php

namespace Drupal\catalog_importer\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a creator field mapper.
 *
 * @FeedsTarget(
 *   id = "creator",
 *   field_types = {"creator"}
 * )
 */
class Creator extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('name')
      ->addProperty('role');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['name'] = trim($values['name'], " \t\n\r\0\x0B-./\\");
    $values['role'] = trim($values['role'], " \t\n\r\0\x0B-./\\");
  }

}
