<?php

namespace Drupal\catalog_item;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a catalog_item type entity.
 */
interface CatalogInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Determines whether the catalog_item type is locked.
   *
   * @return string|false
   *   The module name that locks the catalog or FALSE.
   */
  public function isLocked();


  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Gets whether 'Submitted by' information should be shown.
   *
   * @return bool
   *   TRUE if the submitted by information should be shown.
   */
  public function displaySubmitted();

  /**
   * Sets whether 'Submitted by' information should be shown.
   *
   * @param bool $display_submitted
   *   TRUE if the submitted by information should be shown.
   */
  public function setDisplaySubmitted($display_submitted);

  /**
   * Gets the preview mode.
   *
   * @return int
   *   DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  public function getPreviewMode();

  /**
   * Sets the preview mode.
   *
   * @param int $preview_mode
   *   DRUPAL_DISABLED, DRUPAL_OPTIONAL or DRUPAL_REQUIRED.
   */
  public function setPreviewMode($preview_mode);

  /**
   * Gets the help information.
   *
   * @return string
   *   The help information of this catalog_item type.
   */
  public function getHelp();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this catalog_item type.
   */
  public function getDescription();

}
