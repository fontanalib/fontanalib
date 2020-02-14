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
  public function defaultConfiguration() {
    $options = array();
    $vocabularies = Vocabulary::loadMultiple();

    foreach ($vocabularies as $vid => $vocabulary) {
      if($vocabulary->getThirdPartySetting('fontanalib', 'designate_as_group')){
        $options[$vid] = $vocabulary->label();
      }
    }

    return [
      'bundle_options' => $options,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->setTargetBundles();
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => "Groups Categories",
      '#options' => $configuration['bundle_options'],
      '#default_value' => (array) $configuration['target_bundles'],
      '#size' => 6,
      '#multiple' => TRUE,
      '#element_validate' => [[get_class($this), 'elementValidateFilter']],
      '#ajax' => TRUE,
      '#limit_validation_errors' => [],
    ];
    
    unset($form['auto_create']);
    unset($form['auto_create_bundle']);

    return $form;
  }
  public function setTargetBundles(){
    $configuration = $this->getConfiguration();
    $check_targets = $configuration['target_bundles'] ? array_intersect_key($configuration['target_bundles'], $configuration['bundle_options']) : array_combine(array_keys($configuration['bundle_options']), array_keys($configuration['bundle_options']));
    $check_targets = array_filter($check_targets);
    $configuration['target_bundles'] = empty($check_targets) ? array_combine(array_keys($configuration['bundle_options']), array_keys($configuration['bundle_options'])) : $check_targets;
    $configuration['auto_create'] = FALSE;
    $configuration['auto_create_bundle'] = NULL;
    $configuration['sort'] =  [
      'field' => 'name',
      'direction' => 'asc',
    ];
    
    return $configuration;
  }
  /**
   * {@inheritdoc}
   */
  public static function elementValidateFilter(&$element, FormStateInterface $form_state) {
    $element['#value'] = empty(array_filter($element['#value'])) ? array_combine(array_keys($element['#options']), array_keys($element['#options'])) : $element['#value'];
    $form_state->setValueForElement($element, $element['#value']);
  }
}
