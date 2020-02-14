<?php

/**
 * File for holding helper functions for groups / nodeacceess
 */


namespace Drupal\fontanalib;

use Drupal\Core\Entity\EntityInterface;

class GroupCheck {
  public $groups;
  public $entity;

  public function __construct(EntityInterface $entity){
    $this->entity = $entity;
    $this->setGroups();
  }
  /**
   * Check if entity field content has changed
   * @param  EntityInterface $entity     The entity being saved
   * @return array                    A list of changed field names
   */
  public function entityHasChanged() {
    if (!$this->entity->original) {
      return FALSE;
    }
    $groups = [];
    $changed = FALSE;
    foreach ($this->entity->getFields() as $field_name => $field) {
      if ($field->getFieldDefinition()->getType() !== 'entity_reference'){
        continue;
      }
      if ($targetType = $field->getFieldDefinition()->getItemDefinition()->getSetting('handler') != "fontanalib.groups:taxonomy_term") {
        continue;
      }
      if($this->entity->get($field_name)->equals($this->entity->original->get($field_name))){
        continue;
      } 
      $changed=TRUE;
    }    

     return $changed;
  }


  /**
   * Get groups from entity
   */
  public function setGroups() {
    $groups = array();
    foreach ($this->entity->getFields() as $key => $field) {
      
      if ($field->getFieldDefinition()->getType() !== 'entity_reference'){
        continue;
      }
      if ($targetType = $field->getFieldDefinition()->getItemDefinition()->getSetting('handler') != "fontanalib.groups:taxonomy_term") {
        continue;
      }
      
      $groups += array_map(function (\Drupal\taxonomy\Entity\Term $term) {
        // Need to check if Vocabulary is a "GROUP" type....
        return $term->id();
      }, $field->referencedEntities());
    }  
    $this->groups=$groups;    
  }

  public function getGroups(){
    return $this->groups;
  }

}