<?php

namespace Drupal\catalog_importer\Feeds\Processor;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;

/**
 * Defines a taxonomy term processor.
 *
 * Creates taxonomy terms from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:taxonomy_term",
 *   title = @Translation("Taxonomy Term"),
 *   description = @Translation("Creates terms from feed items."),
 *   entity_type = "taxonomy_term",
 *   arguments = {
 *     "@entity_type.manager",
 *     "@entity.query",
 *     "@entity_type.bundle.info",
 *   },
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class TaxonomyTermProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Taxonomy Term');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Taxonomy Temrs');
  }

}
