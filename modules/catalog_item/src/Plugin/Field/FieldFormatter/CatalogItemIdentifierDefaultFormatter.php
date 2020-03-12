<?php

/**
 * @file
 * Contains \Drupal\catalog_item\Plugin\field\formatter\CatalogItemIdentifierDefaultFormatter.
 */

namespace Drupal\catalog_item\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
/**
 * Plugin implementation of the 'catalog_item_identifier_default' formatter.
 *
 * @FieldFormatter(
 *   id = "catalog_item_identifier_default",
 *   label = @Translation("Identifier default"),
 *   field_types = {
 *     "catalog_item_identifier"
 *   }
 * )
 */
class CatalogItemIdentifierDefaultFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      // Render output using snippets_default theme.
      $source = array(
        '#theme' => 'catalog_item_identifier_default',
        '#id' => $item->id,
        '#type' => $item->type,
        '#cover_image' => $item->cover_image,
      );
      
      $elements[$delta] = array('#markup' => drupal_render($source));
    }

    return $elements;
  }
 }