<?php
/**
* @file
* Contains \Drupal\fontanalib_catalog\Entity\Resource.
*/

namespace Drupal\fontanalib_catalog\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
* Defines the Resource entity.
*
* @ingroup catalog
*
*
* @ContentEntityType(
* id = "resource",
* label = @Translation("Catalog Resource entity"),
* handlers = {
* "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
* "list_builder" = "Drupal\fontanalib_catalog\Entity\Controller\ResourceListBuilder",
* "form" = {
* "add" = "Drupal\fontanalib_catalog\Form\ResourceForm",
* "edit" = "Drupal\fontanalib_catalog\Form\ResourceForm",
* "delete" = "Drupal\fontanalib_catalog\Form\ResourceDeleteForm",
* },
* "access" = "Drupal\fontanalib_catalog\TermAccessControlHandler",
* },
* list_cache_contexts = { "user" },
* base_table = "resource",
* admin_permission = "administer resource entity",
* entity_keys = {
* "id" = "id",
* "uuid" = "uuid",
* "user_id" = "user_id",
* "created" = "created",
* "changed" = "changed",
* "bundle" = "catalog",
* },
* links = {
* "canonical" = "/catalog/{resource}",
* "edit-form" = "/catalog/{resource}/edit",
* "delete-form" = "/catalog/{resource}/delete",
* "collection" = "/catalog/list"
* },
* bundle_entity_type = "catalog",
* field_ui_base_route = "entity.catalog.resource_settings",
* )
*/
class Resource extends ContentEntityBase {

use EntityChangedTrait;

/**
* {@inheritdoc}
*
* When a new entity instance is added, set the user_id entity reference to
* the current user as the creator of the instance.
*/
public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
parent::preCreate($storage_controller, $values);
// Default author to current user.
$values += array(
'user_id' => \Drupal::currentUser()->id(),
);
}

/**
* {@inheritdoc}
*
* Define the field properties here.
*
* Field name, type and size determine the table structure.
*
* In addition, we can define how the field and its content can be manipulated
* in the GUI. The behaviour of the widgets used can be determined here.
*/
public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

// Standard field, used as unique if primary index.
$fields['id'] = BaseFieldDefinition::create('integer')
->setLabel(t('ID'))
->setDescription(t('The ID of the Resource entity.'))
->setReadOnly(TRUE);

// Standard field, unique outside of the scope of the current project.
$fields['uuid'] = BaseFieldDefinition::create('uuid')
->setLabel(t('UUID'))
->setDescription(t('The UUID of the Resource entity.'))
->setReadOnly(TRUE);

// Name field for the contact.
// We set display options for the view as well as the form.
// Users with correct privileges can change the view and edit configuration.
$fields['pl'] = BaseFieldDefinition::create('string')
->setLabel(t('Polish'))
->setDescription(t('Polish version.'))
->setSettings(array(
'default_value' => '',
'max_length' => 255,
'text_processing' => 0,
))
->setDisplayOptions('view', array(
'label' => 'above',
'type' => 'string',
'weight' => -6,
))
->setDisplayOptions('form', array(
'type' => 'string_textfield',
'weight' => -6,
))
->setDisplayConfigurable('form', TRUE)
->setDisplayConfigurable('view', TRUE);

$fields['en'] = BaseFieldDefinition::create('string')
->setLabel(t('English'))
->setDescription(t('English version.'))
->setSettings(array(
'default_value' => '',
'max_length' => 255,
'text_processing' => 0,
))
->setDisplayOptions('view', array(
'label' => 'above',
'type' => 'string',
'weight' => -4,
))
->setDisplayOptions('form', array(
'type' => 'string_textfield',
'weight' => -4,
))
->setDisplayConfigurable('form', TRUE)
->setDisplayConfigurable('view', TRUE);

// Owner field of the contact.
// Entity reference field, holds the reference to the user object.
// The view shows the user name field of the user.
// The form presents a auto complete field for the user name.
$fields['user_id'] = BaseFieldDefinition::create('entity_reference')
->setLabel(t('User Name'))
->setDescription(t('The Name of the associated user.'))
->setSetting('target_type', 'user')
->setSetting('handler', 'default')
->setDisplayOptions('view', array(
'label' => 'above',
'type' => 'author',
'weight' => -3,
))
->setDisplayOptions('form', array(
'type' => 'entity_reference_autocomplete',
'settings' => array(
'match_operator' => 'CONTAINS',
'size' => 60,
'placeholder' => '',
),
'weight' => -3,
))
->setDisplayConfigurable('form', TRUE)
->setDisplayConfigurable('view', TRUE);

$fields['created'] = BaseFieldDefinition::create('created')
->setLabel(t('Created'))
->setDescription(t('The time that the entity was created.'));

$fields['changed'] = BaseFieldDefinition::create('changed')
->setLabel(t('Changed'))
->setDescription(t('The time that the entity was last edited.'));

return $fields;
}

}