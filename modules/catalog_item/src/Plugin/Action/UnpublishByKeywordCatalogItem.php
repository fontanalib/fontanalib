<?php

namespace Drupal\catalog_item\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a catalog_item containing certain keywords.
 *
 * @Action(
 *   id = "catalog_item_unpublish_by_keyword_action",
 *   label = @Translation("Unpublish catalog items containing keyword(s)"),
 *   type = "catalog_item"
 * )
 */
class UnpublishByKeywordCatalogItem extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($catalog_item = NULL) {
    $elements = \Drupal::entityTypeManager()
      ->getViewBuilder('catalog_item')
      ->view(clone $catalog_item);
    $render = \Drupal::service('renderer')->render($elements);
    foreach ($this->configuration['keywords'] as $keyword) {
      if (strpos($render, $keyword) !== FALSE || strpos($catalog_item->label(), $keyword) !== FALSE) {
        $catalog_item->setUnpublished();
        $catalog_item->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'keywords' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#title' => t('Keywords'),
      '#type' => 'textarea',
      '#description' => t('The catalog items will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state->getValue('keywords'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\catalog_item\CatalogItemInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
