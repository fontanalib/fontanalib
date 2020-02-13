<?php

namespace Drupal\fontanalib\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;
/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "fontanalib.groups:taxonomy_term",
 *   label = @Translation("Fontanalib Groups Selection"),
 *   base_plugin_label = @Translation("Fontanalib Groups Selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "fontanalib.groups",
 *   weight = 2
 * )
 */
class GroupSelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#access'] = FALSE;
    $form['auto_create']['#access'] = FALSE;
    return $form;
  }
   /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $options = [];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('taxonomy_term');
    $bundle_names = array_keys($bundles);

    $has_admin_access = $this->currentUser->hasPermission('administer taxonomy');
    $unpublished_terms = [];
    foreach ($bundle_names as $bundle) {
      if ($vocabulary = Vocabulary::load($bundle)) {
        if($vocabulary->getThirdPartySetting('fontanalib', 'designate_as_group')){
          /** @var \Drupal\taxonomy\TermInterface[] $terms */
          if ($terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), 0, NULL, TRUE)) {
            foreach ($terms as $term) {
              if (!$has_admin_access && (!$term->isPublished() || in_array($term->parent->target_id, $unpublished_terms))) {
                $unpublished_terms[] = $term->id();
                continue;
              }
              $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityRepository->getTranslationFromContext($term)->label());
            }
          }
        }
      }
    }

    return $options;
  }
  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    // $target_type = $configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition('taxonomy_term');

    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery();
    $targetBundles = array();
    $vocabularies = Vocabulary::loadMultiple();

    foreach ($vocabularies as $vid => $vocabulary) {
      if($vocabulary->getThirdPartySetting('fontanalib', 'designate_as_group')){
        $targetBundles[] = $vid;
      }
    }
    if (empty($targetBundles)) {
      $query->condition($entity_type->getKey('id'), NULL, '=');
      return $query;
    }
    
    $query->condition($entity_type->getKey('bundle'), $targetBundles, 'IN');
    

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag('taxonomy_term' . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if ($configuration['sort']['field'] !== '_none') {
      $query->sort($configuration['sort']['field'], $configuration['sort']['direction']);
    }

    return $query;
  }
}
