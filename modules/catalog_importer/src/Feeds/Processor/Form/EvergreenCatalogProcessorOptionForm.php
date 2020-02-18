<?php

namespace Drupal\catalog_item\Feeds\Processor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for the CSV parser.
 */
class EvergreenCatalogProcessorOptionForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo Remove hack.
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->plugin->entityType());

    if ($bundle_key = $entity_type->getKey('bundle')) {
      $form['values'][$bundle_key] = [
        '#type' => 'select',
        '#options' => $this->plugin->bundleOptions(),
        '#title' => $this->plugin->bundleLabel(),
        '#required' => TRUE,
        '#default_value' => 'evergreen',
        '#disabled' => TRUE,
      ];
    }

    return $form;
  }

}
