<?php

namespace Drupal\fontanalib\AccessChecks;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;

/**
 * A custom access check for grants form.
 */
class NodeGrantAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   */
  public function access($node, AccountInterface $account) {
    if (!$node) {
      return AccessResult::forbidden();
    }
    $nid = $node;
    $node = Node::load($nid);
    $node_type = $node->getType();
    $config = \Drupal::configFactory()->get('nodeaccess.settings');
    $allowed_types = $config->get('allowed_types');
    if ($node && isset($allowed_types[$node_type]) && !empty($allowed_types[$node_type]) && 
          ($account->hasPermission("grant any $node_type content permissions") ||
          ($account->isAuthenticated() &&
            $account->hasPermission("grant own $node_type content permissions") &&
            $account->id() == $node->getOwnerId()) ||
          $account->hasPermission('grant node permissions') ||
          $account->hasPermission('administer nodeaccess'))) {
      return AccessResult::Allowed();
    }
    return AccessResult::forbidden();
  }

}
