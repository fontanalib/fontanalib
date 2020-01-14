<?php

namespace Drupal\catalog_item;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the catalog_item schema handler.
 */
class CatalogItemStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    /**
     * @HERE database table indexes
     */
    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        'catalog_item__frontpage' => ['promote', 'status', 'sticky', 'created'],
        'catalog_item__title_catalog' => ['title', ['catalog', 4]],
      ];
    }

    // $schema['catalog_taxonomy_index'] = [
    //   'description' => 'Maintains denormalized information about catalog item/term relationships.',
    //   'fields' => [
    //     'nid' => [
    //       'description' => 'The {catalog_item}.nid this record tracks.',
    //       'type' => 'int',
    //       'unsigned' => TRUE,
    //       'not null' => TRUE,
    //       'default' => 0,
    //     ],
    //     'tid' => [
    //       'description' => 'The term ID.',
    //       'type' => 'int',
    //       'unsigned' => TRUE,
    //       'not null' => TRUE,
    //       'default' => 0,
    //     ],
    //     'status' => [
    //       'description' => 'Boolean indicating whether the catalog_item is published (visible to non-administrators).',
    //       'type' => 'int',
    //       'not null' => TRUE,
    //       'default' => 1,
    //     ],
    //     'sticky' => [
    //       'description' => 'Boolean indicating whether the catalog_item is sticky.',
    //       'type' => 'int',
    //       'not null' => FALSE,
    //       'default' => 0,
    //       'size' => 'tiny',
    //     ],
    //     'created' => [
    //       'description' => 'The Unix timestamp when the catalog_item was created.',
    //       'type' => 'int',
    //       'not null' => TRUE,
    //       'default' => 0,
    //     ],
    //   ],
    //   'primary key' => ['nid', 'tid'],
    //   'indexes' => [
    //     'term_catalog_item' => ['tid', 'status', 'sticky', 'created'],
    //   ],
    //   'foreign keys' => [
    //     'tracked_catalog_item' => [
    //       'table' => 'catalog_item',
    //       'columns' => ['nid' => 'nid'],
    //     ],
    //     'term' => [
    //       'table' => 'taxonomy_term_data',
    //       'columns' => ['tid' => 'tid'],
    //     ],
    //   ],
    // ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'catalog_item_revision') {
      switch ($field_name) {
        case 'langcode':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'revision_uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
          break;
      }
    }

    if ($table_name == 'catalog_item_field_data') {
      switch ($field_name) {
        case 'promote':
        case 'status':
        case 'sticky':
        case 'title':
          // Improves the performance of the indexes defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'changed':
        case 'created':
          // @todo Revisit index definitions:
          //   https://www.drupal.org/node/2015277.
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
