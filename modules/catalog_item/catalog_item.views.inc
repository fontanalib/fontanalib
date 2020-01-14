<?php

/**
 * @file
 * Provides views data for catalog_item.module.
 */

use Drupal\field\FieldStorageConfigInterface;

// function catalog_item_views_data() {
  
// }
/**
 * Implements hook_views_data_alter().
 */
function catalog_item_views_data_alter(&$data) {
  $data['catalog_item_field_data']['term_catalog_item_tid'] = [
    'title' => t('Taxonomy terms on catalog items'),
    'help' => t('Relate nodes to taxonomy terms, specifying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
    'relationship' => [
      'id' => 'catalog_item_term_data',
      'label' => t('term'),
      'base' => 'taxonomy_term_field_data',
    ],
    'field' => [
      'title' => t('All taxonomy terms'),
      'help' => t('Display all taxonomy terms associated with a node from specified vocabularies.'),
      'id' => 'catalog_taxonomy_index_tid',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];

  $data['catalog_item_field_data']['term_catalog_item_tid_depth'] = [
    'help' => t('Display content if it has the selected taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
    'real field' => 'nid',
    'argument' => [
      'title' => t('Has taxonomy term ID (with depth)'),
      'id' => 'catalog_taxonomy_index_tid_depth',
      'accept depth modifier' => TRUE,
    ],
    'filter' => [
      'title' => t('Has taxonomy terms (with depth)'),
      'id' => 'catalog_taxonomy_index_tid_depth',
    ],
  ];

  $data['catalog_item_field_data']['term_catalog_item_tid_depth_modifier'] = [
    'title' => t('Has taxonomy term ID depth modifier'),
    'help' => t('Allows the "depth" for Taxonomy: Term ID (with depth) to be modified via an additional contextual filter value.'),
    'argument' => [
      'id' => 'catalog_taxonomy_index_tid_depth_modifier',
    ],
  ];
  $data['taxonomy_term_field_data']['table']['join'] = [
    'catalog_taxonomy_index' => [
      'field' => 'tid',
      'left_field' => 'tid'
      ]
    ];

  $data['taxonomy_term_field_data']['ctid_representative'] = [
    'relationship' => [
      'title' => t('Representative Catalog Item'),
      'label'  => t('Representative Catalog Item'),
      'help' => t('Obtains a single representative Catalog Item for each term, according to a chosen sort criterion.'),
      'id' => 'groupwise_max',
      'relationship field' => 'tid',
      'outer field' => 'taxonomy_term_field_data.tid',
      'argument table' => 'taxonomy_term_field_data',
      'argument field' => 'tid',
      'base'   => 'catalog_item_field_data',
      'field'  => 'nid',
      'relationship' => 'catalog_item_field_data:term_catalog_item_tid',
    ],
  ];
  $data['catalog_taxonomy_index']['table']['group'] = t('Taxonomy term');

  $data['catalog_taxonomy_index']['table']['join'] = [
    'taxonomy_term_field_data' => [
      // links directly to taxonomy_term_field_data via tid
      'left_field' => 'tid',
      'field' => 'tid',
    ],
    'catalog_item_field_data' => [
      // links directly to node via nid
      'left_field' => 'nid',
      'field' => 'nid',
    ],
    'taxonomy_term__parent' => [
      'left_field' => 'entity_id',
      'field' => 'tid',
    ],
  ];

  $data['catalog_taxonomy_index']['nid'] = [
    'title' => t('Catalog items with term'),
    'help' => t('Relate all catalog items tagged with a term.'),
    'relationship' => [
      'id' => 'standard',
      'base' => 'catalog_item',
      'base field' => 'nid',
      'label' => t('catalog item'),
      'skip base' => 'catalog_item',
    ],
  ];
}

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for entity reference fields which reference taxonomy terms.
 * Adds a term relationship to the default field data.
 *
 * @see views_field_default_views_data()
 */
function catalog_item_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  if ($field_storage->getType() == 'entity_reference' && $field_storage->getSetting('target_type') == 'taxonomy_term') {
    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['filter']) && $field_name != 'delta') {
          $data[$table_name][$field_name]['filter']['id'] = 'catalog_taxonomy_index_tid';
        }
      }
    }
  }
}

// /**
//  * Implements hook_views_plugins_argument_validator_alter().
//  *
//  * Extend the generic entity argument validator.
//  *
//  * @see \Drupal\views\Plugin\views\argument_validator\Entity
//  */
// function catalog_item_views_plugins_argument_validator_alter(array &$plugins) {
//   $plugins['entity:taxonomy_term']['title'] = t('Taxonomy term ID');
//   $plugins['entity:taxonomy_term']['class'] = 'Drupal\taxonomy\Plugin\views\argument_validator\Term';
//   $plugins['entity:taxonomy_term']['provider'] = 'taxonomy';
// }
