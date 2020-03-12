<?php

namespace Drupal\catalog_importer\Feeds\Target;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
// use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;

use Drupal\paragraphs\Entity\Paragraph;
////
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\feeds\Annotation\FeedsTarget;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginManager;
use Drupal\field\FieldConfigInterface;

/**
 * Feeds target plugin for Paragraphs fields.
 *
 * @FeedsTarget(
 *   id = "paragraph_target",
 *   field_types = {"entity_reference_revisions"},
 *   arguments = {"@entity.manager"}
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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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

    $form = parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = $this->t('Not yet configured.');
    $paragraphs_type_id = $this->configuration['paragraphs_type'];
    if ($paragraphs_type_id && $paragraphs_type = $this->paragraphsTypeStorage->load($paragraphs_type_id)) {
        $summary = $this->t('Using the %type paragraph.', [
          '%type' => $paragraphs_type->label(),
        ]);
    }
    return $summary . '<br>';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    \Drupal::logger('catalog_importer')->notice('PARAVAL: <pre>@exclude</pre>', array(
      '@exclude'  => print_r($values, TRUE),
    )); 
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
    // \Drupal::logger('catalog_importer')->notice('PARAVALULES: <pre>@exclude</pre>', array(
    //   '@exclude'  => print_r($values, TRUE),
    // )); 
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
