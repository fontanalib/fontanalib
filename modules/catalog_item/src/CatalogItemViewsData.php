<?php

namespace Drupal\catalog_item;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the catalog_item entity type.
 */
class CatalogItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['catalog_item_field_data']['table']['base']['weight'] = -10;
    $data['catalog_item_field_data']['table']['base']['access query tag'] = 'catalog_item_access';
    $data['catalog_item_field_data']['table']['wizard_id'] = 'catalog_item';

    $data['catalog_item_field_data']['nid']['argument'] = [
      'id' => 'catalog_item_nid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ];

    $data['catalog_item_field_data']['title']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['catalog_item_field_data']['title']['field']['link_to_catalog_item default'] = TRUE;

    $data['catalog_item_field_data']['catalog']['argument']['id'] = 'catalog';

    $data['catalog_item_field_data']['langcode']['help'] = $this->t('The language of the content or translation.');

    $data['catalog_item_field_data']['status']['filter']['label'] = $this->t('Published status');
    $data['catalog_item_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['catalog_item_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['catalog_item_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished content if the current user cannot view it.'),
      'filter' => [
        'field' => 'status',
        'id' => 'catalog_item_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    $data['catalog_item_field_data']['promote']['help'] = $this->t('A boolean indicating whether the catalog_item is visible on the front page.');
    $data['catalog_item_field_data']['promote']['filter']['label'] = $this->t('Promoted to front page status');
    $data['catalog_item_field_data']['promote']['filter']['type'] = 'yes-no';

    $data['catalog_item_field_data']['sticky']['help'] = $this->t('A boolean indicating whether the catalog_item should sort to the top of content lists.');
    $data['catalog_item_field_data']['sticky']['filter']['label'] = $this->t('Sticky status');
    $data['catalog_item_field_data']['sticky']['filter']['type'] = 'yes-no';
    $data['catalog_item_field_data']['sticky']['sort']['help'] = $this->t('Whether or not the content is sticky. To list sticky content first, set this to descending.');

    $data['catalog_item']['catalog_item_bulk_form'] = [
      'title' => $this->t('CatalogItem operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple catalog_items.'),
      'field' => [
        'id' => 'catalog_item_bulk_form',
      ],
    ];

    // Bogus fields for aliasing purposes.

    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    $data['catalog_item_field_data']['created_fulldate'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['catalog_item_field_data']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['catalog_item_field_data']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['catalog_item_field_data']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['catalog_item_field_data']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['catalog_item_field_data']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['catalog_item_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['catalog_item_field_data']['changed_year_month'] = [
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['catalog_item_field_data']['changed_year'] = [
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['catalog_item_field_data']['changed_month'] = [
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['catalog_item_field_data']['changed_day'] = [
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['catalog_item_field_data']['changed_week'] = [
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['catalog_item_field_data']['uid']['help'] = $this->t('The user curating the content. If you need more fields than the uid add the curator relationship');
    $data['catalog_item_field_data']['uid']['filter']['id'] = 'user_name';
    $data['catalog_item_field_data']['uid']['relationship']['title'] = $this->t('Catalog curator');
    $data['catalog_item_field_data']['uid']['relationship']['help'] = $this->t('Relate catalog item to the user who created it.');
    $data['catalog_item_field_data']['uid']['relationship']['label'] = $this->t('curator');

    $data['catalog_item']['catalog_item_listing_empty'] = [
      'title' => $this->t('Empty Catalog Item Frontpage behavior'),
      'help' => $this->t('Provides a link to the catalog_item add overview page.'),
      'area' => [
        'id' => 'catalog_item_listing_empty',
      ],
    ];

    $data['catalog_item_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['catalog_item_field_data']['uid_revision']['help'] = $this->t('All catalog_items where a certain user has a revision');
    $data['catalog_item_field_data']['uid_revision']['real field'] = 'nid';
    $data['catalog_item_field_data']['uid_revision']['filter']['id'] = 'catalog_item_uid_revision';
    $data['catalog_item_field_data']['uid_revision']['argument']['id'] = 'catalog_item_uid_revision';

    $data['catalog_item_field_revision']['table']['wizard_id'] = 'catalog_item_revision';

    // Advertise this table as a possible base table.
    $data['catalog_item_field_revision']['table']['base']['help'] = $this->t('Content revision is a history of changes to content.');
    $data['catalog_item_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['catalog_item_field_revision']['nid']['argument'] = [
      'id' => 'catalog_item_nid',
      'numeric' => TRUE,
    ];
    // @todo the NID field needs different behaviour on revision/non-revision
    //   tables. It would be neat if this could be encoded in the base field
    //   definition.
    $data['catalog_item_field_revision']['nid']['relationship']['id'] = 'standard';
    $data['catalog_item_field_revision']['nid']['relationship']['base'] = 'catalog_item_field_data';
    $data['catalog_item_field_revision']['nid']['relationship']['base field'] = 'nid';
    $data['catalog_item_field_revision']['nid']['relationship']['title'] = $this->t('Catalog Item');
    $data['catalog_item_field_revision']['nid']['relationship']['label'] = $this->t('Get the actual catalog item from a content revision.');
    $data['catalog_item_field_revision']['nid']['relationship']['extra'][] = [
      'field' => 'langcode',
      'left_field' => 'langcode',
    ];

    $data['catalog_item_field_revision']['vid'] = [
      'argument' => [
        'id' => 'catalog_item_vid',
        'numeric' => TRUE,
      ],
      'relationship' => [
        'id' => 'standard',
        'base' => 'catalog_item_field_data',
        'base field' => 'vid',
        'title' => $this->t('Catalog Item'),
        'label' => $this->t('Get the actual catalog item from a content revision.'),
        'extra' => [
          [
            'field' => 'langcode',
            'left_field' => 'langcode',
          ],
        ],
      ],
    ] + $data['catalog_item_field_revision']['vid'];

    $data['catalog_item_field_revision']['langcode']['help'] = $this->t('The language the original content is in.');

    $data['catalog_item_revision']['revision_uid']['help'] = $this->t('The user who created the revision.');
    $data['catalog_item_revision']['revision_uid']['relationship']['label'] = $this->t('revision user');
    $data['catalog_item_revision']['revision_uid']['filter']['id'] = 'user_name';

    $data['catalog_item_revision']['table']['join']['catalog_item_field_data']['left_field'] = 'vid';
    $data['catalog_item_revision']['table']['join']['catalog_item_field_data']['field'] = 'vid';

    $data['catalog_item_field_revision']['table']['wizard_id'] = 'catalog_item_field_revision';

    $data['catalog_item_field_revision']['status']['filter']['label'] = $this->t('Published');
    $data['catalog_item_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['catalog_item_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['catalog_item_field_revision']['promote']['help'] = $this->t('A boolean indicating whether the catalog_item is visible on the front page.');

    $data['catalog_item_field_revision']['sticky']['help'] = $this->t('A boolean indicating whether the catalog_item should sort to the top of content lists.');

    $data['catalog_item_field_revision']['langcode']['help'] = $this->t('The language of the content or translation.');

    $data['catalog_item_field_revision']['link_to_revision'] = [
      'field' => [
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'catalog_item_revision_link',
        'click sortable' => FALSE,
      ],
    ];

    $data['catalog_item_field_revision']['revert_revision'] = [
      'field' => [
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'catalog_item_revision_link_revert',
        'click sortable' => FALSE,
      ],
    ];

    $data['catalog_item_field_revision']['delete_revision'] = [
      'field' => [
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the content revision.'),
        'id' => 'catalog_item_revision_link_delete',
        'click sortable' => FALSE,
      ],
    ];

    // Define the base group of this table. Fields that don't have a group defined
    // will go into this field by default.
    $data['catalog_item_access']['table']['group'] = $this->t('Catalog Item access');

    // For other base tables, explain how we join.
    $data['catalog_item_access']['table']['join'] = [
      'catalog_item_field_data' => [
        'left_field' => 'nid',
        'field' => 'nid',
      ],
    ];
    $data['catalog_item_access']['nid'] = [
      'title' => $this->t('Access'),
      'help' => $this->t('Filter by access.'),
      'filter' => [
        'id' => 'catalog_item_access',
        'help' => $this->t('Filter for content by view access. <strong>Not necessary if you are using catalog_item as your base table.</strong>'),
      ],
    ];
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      // $data['catalog_item_field_data']['term_catalog_item_tid'] = [
      //   'title' => $this->t('Taxonomy terms on node'),
      //   'help' => $this->t('Relate nodes to taxonomy terms, specifying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
      //   'relationship' => [
      //     'id' => 'catalog_item_term_data',
      //     'label' => $this->t('term'),
      //     'base' => 'taxonomy_term_field_data',
      //   ],
      //   'field' => [
      //     'title' => $this->t('All taxonomy terms'),
      //     'help' => $this->t('Display all taxonomy terms associated with a node from specified vocabularies.'),
      //     'id' => 'catalog_taxonomy_index_tid',
      //     'no group by' => TRUE,
      //     'click sortable' => FALSE,
      //   ],
      // ];
    
      // $data['catalog_item_field_data']['term_catalog_item_tid_depth'] = [
      //   'help' => $this->t('Display content if it has the selected taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
      //   'real field' => 'nid',
      //   'argument' => [
      //     'title' => $this->t('Has taxonomy term ID (with depth)'),
      //     'id' => 'catalog_taxonomy_index_tid_depth',
      //     'accept depth modifier' => TRUE,
      //   ],
      //   'filter' => [
      //     'title' => $this->t('Has taxonomy terms (with depth)'),
      //     'id' => 'catalog_taxonomy_index_tid_depth',
      //   ],
      // ];
    
      // $data['catalog_item_field_data']['term_catalog_item_tid_depth_modifier'] = [
      //   'title' => $this->t('Has taxonomy term ID depth modifier'),
      //   'help' => $this->t('Allows the "depth" for Taxonomy: Term ID (with depth) to be modified via an additional contextual filter value.'),
      //   'argument' => [
      //     'id' => 'catalog_taxonomy_index_tid_depth_modifier',
      //   ],
      // ];
      // @todo This stuff needs to move to a node field since really it's all
  //   about nodes.
  $data['catalog_taxonomy_index']['tid'] = [
    'group' => t('Catalog Item'),
    'title' => t('Has taxonomy term ID'),
    'help' => t('Display catalog item if it has the selected taxonomy terms.'),
    'argument' => [
      'id' => 'catalog_taxonomy_index_tid',
      'name table' => 'taxonomy_term_field_data',
      'name field' => 'name',
      'empty field name' => t('Uncategorized'),
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_field_data',
    ],
    'filter' => [
      'title' => t('Has taxonomy term'),
      'id' => 'catalog_taxonomy_index_tid',
      'hierarchy table' => 'taxonomy_term__parent',
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_field_data',
      'allow empty' => TRUE,
    ],
  ];

  $data['catalog_taxonomy_index']['status'] = [
    'title' => t('Publish status'),
    'help' => t('Whether or not the content related to a term is published.'),
    'filter' => [
      'id' => 'boolean',
      'label' => t('Published status'),
      'type' => 'yes-no',
    ],
  ];

  $data['catalog_taxonomy_index']['sticky'] = [
    'title' => t('Sticky status'),
    'help' => t('Whether or not the content related to a term is sticky.'),
    'filter' => [
      'id' => 'boolean',
      'label' => t('Sticky status'),
      'type' => 'yes-no',
    ],
    'sort' => [
      'id' => 'standard',
      'help' => t('Whether or not the content related to a term is sticky. To list sticky content first, set this to descending.'),
    ],
  ];

  $data['catalog_taxonomy_index']['created'] = [
    'title' => t('Post date'),
    'help' => t('The date the content related to a term was posted.'),
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  }
    // Add search table, fields, filters, etc., but only if a page using the
    // catalog_item_search plugin is enabled.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchpages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'catalog_item_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['catalog_item_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the catalog_item table (or actually, catalog_item_field_data).
        // Use a Views table alias to allow other modules to use this table too,
        // if they use the search index.
        $data['catalog_item_search_index']['table']['join'] = [
          'catalog_item_field_data' => [
            'left_field' => 'nid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "catalog_item_search_index.type = 'catalog_item_search' AND catalog_item_search_index.langcode = catalog_item_field_data.langcode",
          ],
        ];

        $data['catalog_item_search_total']['table']['join'] = [
          'catalog_item_search_index' => [
            'left_field' => 'word',
            'field' => 'word',
          ],
        ];

        $data['catalog_item_search_dataset']['table']['join'] = [
          'catalog_item_field_data' => [
            'left_field' => 'sid',
            'left_table' => 'catalog_item_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'catalog_item_search_index.type = catalog_item_search_dataset.type AND catalog_item_search_index.langcode = catalog_item_search_dataset.langcode',
            'type' => 'INNER',
          ],
        ];

        $data['catalog_item_search_index']['score'] = [
          'title' => $this->t('Score'),
          'help' => $this->t('The score of the search item. This will not be used if the search filter is not also present.'),
          'field' => [
            'id' => 'search_score',
            'float' => TRUE,
            'no group by' => TRUE,
          ],
          'sort' => [
            'id' => 'search_score',
            'no group by' => TRUE,
          ],
        ];

        $data['catalog_item_search_index']['keys'] = [
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
          'filter' => [
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'catalog_item_search',
          ],
          'argument' => [
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'catalog_item_search',
          ],
        ];

      }
    }

    return $data;
  }

}
