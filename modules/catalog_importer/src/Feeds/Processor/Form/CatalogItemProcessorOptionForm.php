<?php

namespace Drupal\catalog_importer\Feeds\Processor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for the CSV parser.
 */
class CatalogItemProcessorOptionForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo Remove hack.
    $entity_type = \Drupal::entityTypeManager()->getDefinition('catalog_item');


    $form['values'][$bundle_key] = [
      '#type' => 'select',
      '#options' => $this->plugin->bundleOptions(),
      '#title' => $this->plugin->bundleLabel(),
      '#required' => TRUE,
      '#default_value' => $this->plugin->bundle() ?: key($this->plugin->bundleOptions()),
      '#disabled' => $this->plugin->isLocked(),
    ];

    return $form;
  }

}
