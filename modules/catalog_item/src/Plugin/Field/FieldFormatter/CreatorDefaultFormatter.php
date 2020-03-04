<?php

/**
 * @file
 * Contains \Drupal\catalog_item\Plugin\field\formatter\CreatorDefaultFormatter.
 */

namespace Drupal\catalog_item\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'creator_default' formatter.
 *
 * @FieldFormatter(
 *   id = "creator_default",
 *   label = @Translation("Creator default"),
 *   field_types = {
 *     "creator"
 *   }
 * )
 */
class CreatorDefaultFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      // Render output using snippets_default theme.
      $source = array(
        '#theme' => 'creator_default',
        '#name' => $item->name,
        '#role' => $item->role,
      );
      
      $elements[$delta] = array('#markup' => drupal_render($source));
    }

    return $elements;
  }
 }