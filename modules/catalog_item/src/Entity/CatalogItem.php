<?php

namespace Drupal\catalog_item\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\catalog_item\CatalogItemInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the catalog_item entity class.
 *
 * @ContentEntityType(
 *   id = "catalog_item",
 *   label = @Translation("Catalog Item"),
 *   label_collection = @Translation("Catalog Items"),
 *   label_singular = @Translation("catalog item"),
 *   label_plural = @Translation("catalog items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count catalog item",
 *     plural = "@count catalog items"
 *   ),
 *   bundle_label = @Translation("Catalog"),
 *   handlers = {
 *     "storage" = "Drupal\catalog_item\CatalogItemStorage",
 *     "storage_schema" = "Drupal\catalog_item\CatalogItemStorageSchema",
 *     "view_builder" = "Drupal\catalog_item\CatalogItemViewBuilder",
 *     "access" = "Drupal\catalog_item\CatalogItemAccessControlHandler",
 *     "views_data" = "Drupal\catalog_item\CatalogItemViewsData",
 *     "form" = {
 *       "default" = "Drupal\catalog_item\CatalogItemForm",
 *       "delete" = "Drupal\catalog_item\Form\CatalogItemDeleteForm",
 *       "edit" = "Drupal\catalog_item\CatalogItemForm",
 *       "delete-multiple-confirm" = "Drupal\catalog_item\Form\DeleteMultiple"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\catalog_item\Entity\CatalogItemRouteProvider",
 *     },
 *     "list_builder" = "Drupal\catalog_item\CatalogItemListBuilder",
 *     "translation" = "Drupal\catalog_item\CatalogItemTranslationHandler"
 *   },
 *   base_table = "catalog_item",
 *   data_table = "catalog_item_field_data",
 *   revision_table = "catalog_item_revision",
 *   revision_data_table = "catalog_item_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   list_cache_contexts = { "user.catalog_item_grants:view" },
 *   entity_keys = {
 *     "id" = "nid",
 *     "revision" = "vid",
 *     "bundle" = "catalog",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   bundle_entity_type = "catalog",
 *   field_ui_base_route = "entity.catalog.edit_form",
 *   common_reference_target = TRUE,
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/catalog-item/{catalog_item}",
 *     "delete-form" = "/catalog-item/{catalog_item}/delete",
 *     "delete-multiple-form" = "/admin/content/catalog-item/delete",
 *     "edit-form" = "/catalog-item/{catalog_item}/edit",
 *     "version-history" = "/catalog-item/{catalog_item}/revisions",
 *     "revision" = "/catalog-item/{catalog_item}/revisions/{catalog_item_revision}/view",
 *     "create" = "/catalog-item",
 *   }
 * )
 */
class CatalogItem extends EditorialContentEntityBase implements CatalogItemInterface {

  use EntityOwnerTrait;

  /**
   * Whether the catalog_item is being previewed or not.
   *
   * The variable is set to public as it will give a considerable performance
   * improvement. See https://www.drupal.org/catalog_item/2498919.
   *
   * @var true|null
   *   TRUE if the catalog_item is being previewed and NULL if it is not.
   */
  public $in_preview = NULL;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the catalog_item owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing catalog_item without adding a new revision, we
      // need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update the catalog_item access table for this catalog_item, but only if it is the
    // default revision. There's no need to delete existing records if the catalog_item
    // is new.
    if ($this->isDefaultRevision()) {
      /** @var \Drupal\catalog_item\CatalogItemAccessControlHandlerInterface $access_control_handler */
      $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('catalog_item');
      $grants = $access_control_handler->acquireGrants($this);
      \Drupal::service('catalog_item.grant_storage')->write($this, $grants, NULL, $update);
    }

    // Reindex the catalog_item when it is updated. The catalog_item is automatically indexed
    // when it is added, simply by being added to the catalog_item table.
    if ($update) {
      catalog_item_reindex_catalog_item_search($this->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Ensure that all catalog_items deleted are removed from the search index.
    if (\Drupal::hasService('search.index')) {
      /** @var \Drupal\search\SearchIndexInterface $search_index */
      $search_index = \Drupal::service('search.index');
      foreach ($entities as $entity) {
        $search_index->clear('catalog_item_search', $entity->nid->value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $catalog_items) {
    parent::postDelete($storage, $catalog_items);
    \Drupal::service('catalog_item.grant_storage')->deleteCatalogItemRecords(array_keys($catalog_items));
  }

  /**
   * {@inheritdoc}
   */
  public function getCatalog() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPromoted() {
    return (bool) $this->get('promote')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPromoted($promoted) {
    $this->set('promote', $promoted ? CatalogItemInterface::PROMOTED : CatalogItemInterface::NOT_PROMOTED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return (bool) $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->set('sticky', $sticky ? CatalogItemInterface::STICKY : CatalogItemInterface::NOT_STICKY);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid']
      ->setLabel(t('Curated by'))
      ->setDescription(t('The username of the content curator.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Added on'))
      ->setDescription(t('The time that the catalog item was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the catalog item was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['promote'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Promoted to front page'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sticky'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sticky at top of lists'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
