<?php

namespace Drupal\fontanalib;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Class definition.
 *
 * @category NodeViewPermissionsPermissions
 *
 * @package Access Control
 */
class NodeGrantAccessPermissions {
  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function nodeTypePermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "grant any $type_id content permissions" => [
        'title' => $this->t('%type_name: grant access permissions on any content', $type_params),
      ],
      "grant own $type_id content permissions" => [
        'title' => $this->t('%type_name: grant access permissions on own content (author)', $type_params),
      ],
    ];
  }
}
