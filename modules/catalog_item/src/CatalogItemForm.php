<?php

namespace Drupal\catalog_item;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the catalog_item edit forms.
 *
 * @internal
 */
class CatalogItemForm extends ContentEntityForm {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a CatalogItemForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, PrivateTempStoreFactory $temp_store_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountInterface $current_user, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->tempStoreFactory = $temp_store_factory;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('tempstore.private'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Try to restore from temp store, this must be done before calling
    // parent::form().
    $store = $this->tempStoreFactory->get('catalog_item_preview');

    // Attempt to load from preview when the uuid is present unless we are
    // rebuilding the form.
    $request_uuid = \Drupal::request()->query->get('uuid');
    if (!$form_state->isRebuilding() && $request_uuid && $preview = $store->get($request_uuid)) {
      /** @var $preview \Drupal\Core\Form\FormStateInterface */

      $form_state->setStorage($preview->getStorage());
      $form_state->setUserInput($preview->getUserInput());

      // Rebuild the form.
      $form_state->setRebuild();

      // The combination of having user input and rebuilding the form means
      // that it will attempt to cache the form state which will fail if it is
      // a GET request.
      $form_state->setRequestMethod('POST');

      $this->entity = $preview->getFormObject()->getEntity();
      $this->entity->in_preview = NULL;

      $form_state->set('has_been_previewed', TRUE);
    }

    /** @var \Drupal\catalog_item\CatalogItemInterface $catalog_item */
    $catalog_item = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit @catalog</em> @title', [
        '@catalog' => catalog_item_get_catalog_label($catalog_item),
        '@title' => $catalog_item->label(),
      ]);
    }

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $catalog_item->getChangedTime(),
    ];

    $form = parent::form($form, $form_state);

    $form['advanced']['#attributes']['class'][] = 'entity-meta';
    $form['advanced']['#type'] = 'container';

    $form['meta'] = [
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -10,
      '#title' => $this->t('Status'),
      '#attributes' => ['class' => ['entity-meta__header']],
      '#tree' => TRUE,
      '#access' => $this->currentUser->hasPermission('administer catalog items'),
    ];
    $form['meta']['published'] = [
      '#type' => 'item',
      '#markup' => $catalog_item->isPublished() ? $this->t('Published') : $this->t('Not published'),
      '#access' => !$catalog_item->isNew(),
      '#wrapper_attributes' => ['class' => ['entity-meta__title']],
    ];
    $form['meta']['changed'] = [
      '#type' => 'item',
      '#title' => $this->t('Last saved'),
      '#markup' => !$catalog_item->isNew() ? $this->dateFormatter->format($catalog_item->getChangedTime(), 'short') : $this->t('Not saved yet'),
      '#wrapper_attributes' => ['class' => ['entity-meta__last-saved', 'container-inline']],
    ];
    $form['meta']['curator'] = [
      '#type' => 'item',
      '#title' => $this->t('Curator'),
      '#markup' => $catalog_item->getOwner()->getAccountName(),
      '#wrapper_attributes' => ['class' => ['entity-meta__curator', 'container-inline']],
    ];

    $form['status']['#group'] = 'footer';

    // CatalogItem author information for administrators.
    $form['curator'] = [
      '#type' => 'details',
      '#title' => t('Curator information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['catalog_item-form-curator'],
      ],
      '#attached' => [
        'library' => ['catalog_item/drupal.catalog_item'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];


    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'curator';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'curator';
    }

    // CatalogItem options for administrators.
    $form['options'] = [
      '#type' => 'details',
      '#title' => t('Promotion options'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['catalog_item-form-options'],
      ],
      '#attached' => [
        'library' => ['catalog_item/drupal.catalog_item'],
      ],
      '#weight' => 95,
      '#optional' => TRUE,
    ];

    if (isset($form['promote'])) {
      $form['promote']['#group'] = 'options';
    }

    if (isset($form['sticky'])) {
      $form['sticky']['#group'] = 'options';
    }

    $form['#attached']['library'][] = 'catalog_item/form';

    $form['#theme'] = ['catalog_item_edit_form'];
    
    
    $form['meta']['#access'] = TRUE;
    
  
    $form['revision_information']['#type'] = 'container';
    $form['revision_information']['#group'] = 'meta';
    return $form;
  }

  /**
   * Entity builder updating the catalog_item status with the submitted value.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\catalog_item\CatalogItemInterface $catalog_item
   *   The catalog_item updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\catalog_item\CatalogItemForm::form()
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0.
   *   The "Publish" button was removed.
   */
  public function updateStatus($entity_type_id, CatalogItemInterface $catalog_item, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#published_status'])) {
      $element['#published_status'] ? $catalog_item->setPublished() : $catalog_item->setUnpublished();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $catalog_item = $this->entity;
    /**
     * Check catalog vs. type @HERE
     */
    $preview_mode = $catalog_item->catalog->entity->getPreviewMode();

    $element['submit']['#access'] = $preview_mode != DRUPAL_REQUIRED || $form_state->get('has_been_previewed');

    $element['preview'] = [
      '#type' => 'submit',
      '#access' => $preview_mode != DRUPAL_DISABLED && ($catalog_item->access('create') || $catalog_item->access('update')),
      '#value' => t('Preview'),
      '#weight' => 20,
      '#submit' => ['::submitForm', '::preview'],
    ];

    if (array_key_exists('delete', $element)) {
      $element['delete']['#weight'] = 100;
    }

    return $element;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('catalog_item_preview');
    $this->entity->in_preview = TRUE;
    $store->set($this->entity->uuid(), $form_state);

    $route_parameters = [
      'catalog_item_preview' => $this->entity->uuid(),
      'view_mode_id' => 'full',
    ];

    $options = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('entity.catalog_item.preview', $route_parameters, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $catalog_item = $this->entity;
    $insert = $catalog_item->isNew();
    $catalog_item->save();
    $catalog_item_link = $catalog_item->toLink($this->t('View'))->toString();
    $context = ['@catalog' => $catalog_item->getCatalog(), '%title' => $catalog_item->label(), 'link' => $catalog_item_link];
    $t_args = ['@catalog' => catalog_item_get_catalog_label($catalog_item), '%title' => $catalog_item->toLink()->toString()];

    if ($insert) {
      $this->logger('content')->notice('@catalog: added %title.', $context);
      $this->messenger()->addStatus($this->t('@catalog item %title has been created.', $t_args));
    }
    else {
      $this->logger('content')->notice('@catalog: updated %title.', $context);
      $this->messenger()->addStatus($this->t('@catalog %title has been updated.', $t_args));
    }

    if ($catalog_item->id()) {
      $form_state->setValue('nid', $catalog_item->id());
      $form_state->set('nid', $catalog_item->id());
      if ($catalog_item->access('view')) {
        $form_state->setRedirect(
          'entity.catalog_item.canonical',
          ['catalog_item' => $catalog_item->id()]
        );
      }
      else {
        $form_state->setRedirect('<front>');
      }

      // Remove the preview entry from the temp store, if any.
      $store = $this->tempStoreFactory->get('catalog_item_preview');
      $store->delete($catalog_item->uuid());
    }
    else {
      // In the unlikely case something went wrong on save, the catalog_item will be
      // rebuilt and catalog_item form redisplayed the same way as in preview.
      $this->messenger()->addError($this->t('The post could not be saved.'));
      $form_state->setRebuild();
    }
  }

}
