<?php

namespace Drupal\catalog_importer\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
// use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Feeds target plugin for Paragraphs fields.
 *
 * @FeedsTarget(
 *   id = "paragraph_target",
 *   field_types = {"entity_reference_revisions"},
 *   arguments = {"@entity.manager", "@current_user"}
 * )
 */
class Paragraphs extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * The paragraph storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paragraphStorage;

  /**
   * The paragraphs type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paragraphsTypeStorage;

  /**
   * The field config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * Constructs the target plugin.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
    $this->paragraphStorage = $entity_type_manager->getStorage('paragraph');
    $this->paragraphsTypeStorage = $entity_type_manager->getStorage('paragraphs_type');
    $this->fieldConfigStorage = $entity_type_manager->getStorage('field_config');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'paragraphs_type' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['paragraphs_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraphs type'),
      '#required' => TRUE,
      '#options' => array_map(function(EntityInterface $paragraphs_type) {
        return $paragraphs_type->label();
      }, $this->paragraphsTypeStorage->loadMultiple()),
      '#default_value' => $this->configuration['paragraphs_type'],
    ];

    // // Load and filter field configs to create options.
    // /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    // $field_configs = $this->fieldConfigStorage->loadByProperties([
    //   'entity_type' => 'paragraph',
    //   'bundle' => $this->configuration['paragraphs_type'],
    // ]);
    // $field_options = [];
    // foreach ($field_configs as $field_config) {
    //   if (in_array($field_config->getType(), ['text', 'text_long', 'text_with_summary'])) {
    //     $field_options[$field_config->getName()] = $field_config->label();
    //   }
    // }

    // $form['paragraph_field'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Paragraph field'),
    //   '#description' => $this->t('<strong>Note:</strong> Field options do not appear until a type has been chosen and saved.'),
    //   '#options' => $field_options,
    // ];

    $form = parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = $this->t('Not yet configured.');
    $paragraphs_type_id = $this->configuration['paragraphs_type'];
    // $paragraph_field_name = $this->configuration['paragraph_field'];
    if ($paragraphs_type_id && $paragraphs_type = $this->paragraphsTypeStorage->load($paragraphs_type_id)) {
      // if ($paragraph_field_name && $paragraph_field = $this->fieldConfigStorage->load('paragraph.' . $paragraphs_type_id . '.' . $paragraph_field_name)) {
        $summary = $this->t('Using the %type paragraph.', [
          '%type' => $paragraphs_type->label(),
        ]);
      // }
    }
    return $summary . '<br>';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    parent::prepareValue($delta, $values);
    // $paragraph = Paragraph::create(['type' => $this->configuration['paragraphs_type'],]);
    // dd($values);
    // \Drupal::logger('catalog_importer')->debug(print_r($values, TRUE));
    // $paragraph = $this->paragraphStorage->create([
    //   'type' => $this->configuration['paragraphs_type'],
    //   $this->configuration['paragraph_field'] => $values,
    // ]);
    // $values = ['entity' => $paragraph];
  }
    /**
   * Prepares the the values that will be mapped to an entity.
   *
   * @param array $values
   *   The values.
   */
  protected function prepareValues(array $values) {
    \Drupal::logger('catalog_importer')->debug(print_r($values, TRUE));
    $return = [];
    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $return[] = $columns;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        $this->addMessage($e->getFormattedMessage(), 'error');
      }
    }
   
    return $return;
  }

}
