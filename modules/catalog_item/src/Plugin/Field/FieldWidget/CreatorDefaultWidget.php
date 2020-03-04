<?php
/**
 * @file
 * Contains \Drupal\catalog_item\Plugin\Field\FieldWidget\CreatorDefaultWidget.
 */

namespace Drupal\catalog_item\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
/**
* Plugin implementation of the 'creator_default' widget.
*
* @FieldWidget(
*   id = "creator_default",
*   label = @Translation("Creator default"),
*   field_types = {
*     "creator"
*   }
* )
*/
class CreatorDefaultWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['name'] = array(
          '#title' => $this->t('Creator Name'),
          '#type' => 'textfield',
          '#default_value' => isset($items[$delta]->name) ? $items[$delta]->name : NULL,
        );
    $element['role'] = array(
          '#title' => $this->t('Creator Role'),
          '#type' => 'textfield',
          '#default_value' => isset($items[$delta]->role) ? $items[$delta]->role : NULL,
        );
    return $element;
  }
 }