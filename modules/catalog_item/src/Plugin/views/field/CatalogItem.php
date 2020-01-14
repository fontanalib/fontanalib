<?php

namespace Drupal\catalog_item\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a catalog_item.
 * Definition terms:
 * - link_to_catalog_item default: Should this field have the checkbox "link to catalog_item" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("catalog_item")
 */
class CatalogItem extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby
    if (!empty($this->options['link_to_catalog_item'])) {
      $this->additional_fields['nid'] = ['table' => 'catalog_item_field_data', 'field' => 'nid'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_catalog_item'] = ['default' => isset($this->definition['link_to_catalog_item default']) ? $this->definition['link_to_catalog_item default'] : FALSE];
    return $options;
  }

  /**
   * Provide link to catalog_item option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_catalog_item'] = [
      '#title' => $this->t('Link this field to the original piece of content'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_catalog_item']),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the catalog_item.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_catalog_item']) && !empty($this->additional_fields['nid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['url'] = Url::fromRoute('entity.catalog_item.canonical', ['catalog_item' => $this->getValue($values, 'nid')]);
        if (isset($this->aliases['langcode'])) {
          $languages = \Drupal::languageManager()->getLanguages();
          $langcode = $this->getValue($values, 'langcode');
          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
