<?php

namespace Drupal\catalog_item\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Filter handler to accept a user id to check for catalog_items that
 * user posted or created a revision on.
 *
 * @ViewsArgument("catalog_item_uid_revision")
 */
class UidRevision extends Uid {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(0, "$this->tableAlias.uid = $placeholder OR ((SELECT COUNT(DISTINCT vid) FROM {catalog_item_revision} cr WHERE cr.revision_uid = $placeholder AND cr.nid = $this->tableAlias.nid) > 0)", [$placeholder => $this->argument]);
  }

}
