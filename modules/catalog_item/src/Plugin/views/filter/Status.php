<?php

namespace Drupal\catalog_item\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("catalog_item_status")
 */
class Status extends FilterPluginBase {

  public function adminSummary() {}

  protected function operatorForm(&$form, FormStateInterface $form_state) {}

  public function canExpose() {
    return FALSE;
  }

  public function query() {
    $table = $this->ensureMyTable();
    $snippet = "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_CATALOG_ITEMS*** = 1) OR ***BYPASS_CATALOG_ACCESS*** = 1";
    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $snippet .= ' OR ***VIEW_ANY_UNPUBLISHED_CATALOG_ITEMS*** = 1';
    }
    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

}
