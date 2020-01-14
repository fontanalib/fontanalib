<?php

namespace Drupal\catalog_item;

use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for catalog_item type forms.
 *
 * @internal
 */
class CatalogForm extends BundleEntityFormBase {
  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = [
    'entityManager' => 'entity.manager',
  ];

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs the CatalogForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add catalog');
      $fields = $this->entityFieldManager->getBaseFieldDefinitions('catalog_item');
      // Create a catalog_item with a fake bundle using the type's UUID so that we can
      // get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      $catalog_item = $this->entityTypeManager->getStorage('catalog_item')->create(['catalog' => $type->uuid()]);
    }
    else {
      $form['#title'] = $this->t('Edit %label catalog', ['%label' => $type->label()]);
      $fields = $this->entityFieldManager->getFieldDefinitions('catalog_item', $type->id());
      // Create a catalog_item to get the current values for workflow settings fields.
      $catalog_item = $this->entityTypeManager->getStorage('catalog_item')->create(['catalog' => $type->id()]);
    }

    $form['name'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this catalog. This text will be displayed as part of the list on the <em>Add content</em> page. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['catalog'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => [
        'exists' => ['Drupal\catalog_item\Entity\Catalog', 'load'],
        'source' => ['name'],
      ],
      '#description' => t('A unique machine-readable name for this catalog. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %catalog_item-add page, in which underscores will be converted into hyphens.', [
        '%catalog_item-add' => t('Add catalog item'),
      ]),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new catalog item</em> page.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => ['catalog_item/drupal.catalogs'],
      ],
    ];

    $form['submission'] = [
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];
    $form['submission']['title_label'] = [
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    ];
    $form['submission']['preview_mode'] = [
      '#type' => 'radios',
      '#title' => t('Preview before submitting'),
      '#default_value' => $type->getPreviewMode(),
      '#options' => [
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ],
    ];
    $form['submission']['help'] = [
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => t('This text will be displayed at the top of the page when creating or editing content of this type.'),
    ];
    $form['workflow'] = [
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#group' => 'additional_settings',
    ];
    $workflow_options = [
      'status' => $catalog_item->status->value,
      'promote' => $catalog_item->promote->value,
      'sticky' => $catalog_item->sticky->value,
      'revision' => $type->shouldCreateNewRevision(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => [
        'status' => t('Published'),
        'promote' => t('Promoted to front page'),
        'sticky' => t('Sticky at top of lists'),
        'revision' => t('Create new revision'),
      ],
      '#description' => t('Users with the <em>Administer content</em> permission will be able to override these options.'),
    ];
    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('catalog_item', $type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'catalog_item',
          'bundle' => $type->id(),
        ],
        '#default_value' => $language_configuration,
      ];
    }
    $form['display'] = [
      '#type' => 'details',
      '#title' => t('Display settings'),
      '#group' => 'additional_settings',
    ];
    $form['display']['display_submitted'] = [
      '#type' => 'checkbox',
      '#title' => t('Display curator and date information'),
      '#default_value' => $type->displaySubmitted(),
      '#description' => t('Curator username and publish date will be displayed.'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save catalog');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('catalog'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('catalog', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $this->messenger()->addStatus($this->t('Attempting to save %name - @form ', ['%name' => $type->id(), '@form'=>$form_state->getValue('catalog')]));

    $type->setNewRevision($form_state->getValue(['options', 'revision']));
    $type->set('catalog', trim($type->id()));
    $type->set('name', trim($type->label()));

    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The catalog %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      \Drupal::logger('catalog_importer')->notice('catalog_item_add_description_field mane is %name', array(
        '%name' => $type->id()
      ));
      catalog_item_add_description_field($type);
      $this->messenger()->addStatus($this->t('The catalog %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $type->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('catalog_item')->notice('Added catalog %name.', $context);
    }

    $fields = $this->entityFieldManager->getFieldDefinitions('catalog_item', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($type->id())->setLabel($title_label)->save();
    }
    // Update workflow options.
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    $catalog_item = $this->entityTypeManager->getStorage('catalog_item')->create(['catalog' => $type->id()]);
    foreach (['status', 'promote', 'sticky'] as $field_name) {
      $value = (bool) $form_state->getValue(['options', $field_name]);
      if ($catalog_item->$field_name->value != $value) {
        $fields[$field_name]->getConfig($type->id())->setDefaultValue($value)->save();
      }
    }

    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
