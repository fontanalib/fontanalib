<?php

namespace Drupal\catalog_item;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a catalog_item entity.
 */
interface CatalogItemInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the catalog_item is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the catalog_item is published.
   */
  const PUBLISHED = 1;

  /**
   * Denotes that the catalog_item is not promoted to the front page.
   */
  const NOT_PROMOTED = 0;

  /**
   * Denotes that the catalog_item is promoted to the front page.
   */
  const PROMOTED = 1;

  /**
   * Denotes that the catalog_item is not sticky at the top of the page.
   */
  const NOT_STICKY = 0;

  /**
   * Denotes that the catalog_item is sticky at the top of the page.
   */
  const STICKY = 1;

  /**
   * Gets the catalog_item type.
   *
   * @return string
   *   The catalog item type.
   */
  public function getCatalog();

  /**
   * Gets the catalog_item title.
   *
   * @return string
   *   Title of the catalog_item.
   */
  public function getTitle();

  /**
   * Sets the catalog_item title.
   *
   * @param string $title
   *   The catalog_item title.
   *
   * @return $this
   *   The called catalog_item entity.
   */
  public function setTitle($title);

  /**
   * Gets the catalog_item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the catalog_item.
   */
  public function getCreatedTime();

  /**
   * Sets the catalog_item creation timestamp.
   *
   * @param int $timestamp
   *   The catalog_item creation timestamp.
   *
   * @return $this
   *   The called catalog_item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the catalog_item promotion status.
   *
   * @return bool
   *   TRUE if the catalog_item is promoted.
   */
  public function isPromoted();

  /**
   * Sets the catalog_item promoted status.
   *
   * @param bool $promoted
   *   TRUE to set this catalog_item to promoted, FALSE to set it to not promoted.
   *
   * @return $this
   *   The called catalog_item entity.
   */
  public function setPromoted($promoted);

  /**
   * Returns the catalog_item sticky status.
   *
   * @return bool
   *   TRUE if the catalog_item is sticky.
   */
  public function isSticky();

  /**
   * Sets the catalog_item sticky status.
   *
   * @param bool $sticky
   *   TRUE to set this catalog_item to sticky, FALSE to set it to not sticky.
   *
   * @return $this
   *   The called catalog_item entity.
   */
  public function setSticky($sticky);

  /**
   * Gets the catalog_item revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the catalog_item revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   *   The called catalog_item entity.
   */
  public function setRevisionCreationTime($timestamp);


}
