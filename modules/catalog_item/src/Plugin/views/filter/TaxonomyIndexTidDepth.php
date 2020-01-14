<?php

namespace Drupal\catalog_item\Plugin\views\filter;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the catalog_item table and has some restrictions,
 * because it uses a subquery to find catalog_items with.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("catalog_taxonomy_index_tid_depth")
 */
class TaxonomyIndexTidDepth extends TaxonomyIndexTid {

  public function operatorOptions($which = 'title') {
    return [
      'or' => $this->t('Is one of'),
    ];
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['depth'] = ['default' => 0];

    return $options;
  }

  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    $form['depth'] = [
      '#type' => 'weight',
      '#title' => $this->t('Depth'),
      '#default_value' => $this->options['depth'],
      '#description' => $this->t('The depth will match catalog_items tagged with terms in the hierarchy. For example, if you have the term "fruit" and a child term "apple", with a depth of 1 (or higher) then filtering for the term "fruit" will get catalog_items that are tagged with "apple" as well as "fruit". If negative, the reverse is true; searching for "apple" will also pick up catalog_items tagged with "fruit" if depth is -1 (or lower).'),
    ];
  }

  public function query() {
    // If no filter values are present, then do nothing.
    if (count($this->value) == 0) {
      return;
    }
    elseif (count($this->value) == 1) {
      // Sometimes $this->value is an array with a single element so convert it.
      if (is_array($this->value)) {
        $this->value = current($this->value);
      }
      $operator = '=';
    }
    else {
      $operator = 'IN';
    }

    // The normal use of ensureMyTable() here breaks Views.
    // So instead we trick the filter into using the alias of the base table.
    //   See https://www.drupal.org/node/271833.
    // If a relationship is set, we must use the alias it provides.
    if (!empty($this->relationship)) {
      $this->tableAlias = $this->relationship;
    }
    // If no relationship, then use the alias of the base table.
    else {
      $this->tableAlias = $this->query->ensureTable($this->view->storage->get('base_table'));
    }

    // Now build the subqueries.
    $subquery = Database::getConnection()->select('catalog_taxonomy_index', 'ctn');
    $subquery->addField('ctn', 'nid');
    $where = (new Condition('OR'))->condition('ctn.tid', $this->value, $operator);
    $last = "ctn";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term__parent', 'th', "th.entity_id = ctn.tid");
      $last = "th";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term__parent', "th$count", "$last.parent_target_id = th$count.entity_id");
        $where->condition("th$count.entity_id", $this->value, $operator);
        $last = "th$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $field = $count == 1 ? 'tid' : 'entity_id';
        $subquery->leftJoin('taxonomy_term__parent', "th$count", "$last.$field = th$count.parent_target_id");
        $where->condition("th$count.entity_id", $this->value, $operator);
        $last = "th$count";
      }
    }

    $subquery->condition($where);
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", $subquery, 'IN');
  }

}
