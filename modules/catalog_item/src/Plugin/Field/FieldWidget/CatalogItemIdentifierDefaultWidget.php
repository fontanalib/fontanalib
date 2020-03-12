<?php
/**
 * @file
 * Contains \Drupal\catalog_item\Plugin\Field\FieldWidget\CatalogItemIdentifierDefaultWidget.
 */

namespace Drupal\catalog_item\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
/**
* Plugin implementation of the 'catalog_item_identifier' widget.
*
* @FieldWidget(
*   id = "catalog_item_identifier_default",
*   label = @Translation("Catalog Identifier default"),
*   field_types = {
*     "catalog_item_identifier"
*   }
* )
*/
class CatalogItemIdentifierDefaultWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['id'] = array(
          '#title' => $this->t('Identifier ID'),
          '#type' => 'textfield',
          '#default_value' => isset($items[$delta]->id) ? $items[$delta]->id : NULL,
        );
    $element['type'] = array(
          '#title' => $this->t('Identifier Type'),
          '#type' => 'textfield',
          '#default_value' => isset($items[$delta]->type) ? $items[$delta]->type : NULL,
        );
    $element['cover_image'] = array(
      '#title' => $this->t('Use as cover image'),
      '#type' => 'checkbox',
      '#default_value' => !empty($items[$delta]->cover_image),
    );
    return $element;
  }
 }