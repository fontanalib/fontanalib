<?php
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
/**
 * @file
 * Content administration and module settings user interface.
 */

/**
 * Updates all entities in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $entities
 *   Array of entity ids or entities to update.
 * @param array $updates
 *   Array of key/value pairs with entity field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $entities contains an array of entity IDs to be loaded, FALSE
 *   if it contains fully loaded entities. Defaults to FALSE.
 * @param bool $revisions
 *   (optional) TRUE if $entities contains an array of revision IDs instead of
 *   entity IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 */
function entity_mass_update(string $entity_type, array $entities, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of entities.
  if (count($entities) > 10) {
    $batch = [
      'operations' => [
        ['_entity_mass_update_batch_process', [$entity_type, $entities, $updates, $langcode, $load, $revisions]],
      ],
      'finished' => '_entity_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'fontanalib') . '/fontanalib.admin.inc',
    ];
    batch_set($batch);
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('entity');
    if ($load && !$revisions) {
      $entities = $storage->loadMultiple($entities);
    }
    foreach ($entities as $entity) {
      if ($load && $revisions) {
        $entity = $storage->loadRevision($entity);
      }
      _entity_mass_update_helper($entity_type, $entity, $updates, $langcode);
    }
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
}

/**
 * Updates individual entities when fewer than 10 are queued.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   A entity to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   An updated entity object.
 *
 * @see entity_mass_update()
 */
function _entity_mass_update_helper(string $entity_type, EntityInterface $entity, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? [$langcode] : array_keys($entity->getTranslationLanguages());
  // For efficiency manually save the original entity before applying any changes.
  $entity->original = clone $entity;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $entity->getTranslation($langcode)->$name = $value;
    }
  }
  $entity->save();
  return $entity;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for entity_mass_update().
 *
 * @param array $entities
 *   An array of entity IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $entities contains an array of entity IDs to be loaded, FALSE if it
 *   contains fully loaded entities.
 * @param bool $revisions
 *   (optional) TRUE if $entities contains an array of revision IDs instead of
 *   entity IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 * @param array|\ArrayAccess $context
 *   An array of contextual key/values.
 */
function _entity_mass_update_batch_process(string $entity_type, array $entities, array $updates, $langcode, $load, $revisions, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($entities);
    $context['sandbox'][$entity_type] = $entities;
  }

  // Process entities by groups of 5.
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $count = min(5, count($context['sandbox'][$entity_type]));
  for ($i = 1; $i <= $count; $i++) {
    // For each id, load the entity, reset the values, and save it.
    $entity = array_shift($context['sandbox'][$entity_type]);
    if ($load) {
      $entity = $revisions ?
        $storage->loadRevision($entity) : $storage->load($entity);
    }
    $entity = _entity_mass_update_helper($entity_type, $entity, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = \Drupal\Core\Link::fromTextAndUrl($entity->label(), $entity->toUrl()); //\Drupal::l($entity->label(), $entity->toUrl());

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Implements callback_batch_finished().
 *
 * Reports the 'finished' status of batch operation for entity_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to entities updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _entity_mass_update_batch_process()
 */
function _entity_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
  else {
    \Drupal::messenger()->addError(t('An error occurred and processing did not complete.'));
    $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = [
      '#theme' => 'item_list',
      '#items' => $results,
    ];
    $message .= \Drupal::service('renderer')->render($item_list);
    \Drupal::messenger()->addStatus($message);
  }
}
/**
 * Helper function for the real hook_form_vocabulary_form_alter().
 *
 * @see fontanalib_form_vocabulary_form_alter()
 */
function _fontanalib_form_vocabulary_form_alter(array &$form, FormStateInterface $form_state) {
  $config = \Drupal::config('fontanalib.settings');

  /** @var \Drupal\node\NodeTypeInterface $type */
  $vocabulary = $form_state->getFormObject()->getEntity();

}

/**
 * Helper function for the real hook_form_node_type_form_alter().
 *
 * @see fontanalib_form_node_type_form_alter()
 */
function _fontanalib_form_node_type_form_alter(array &$form, FormStateInterface $form_state) {
  $config = \Drupal::config('fontanalib.settings');
  $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
  $role_options = [];
  foreach($roles as $role){
    if(!$role->hasPermission('view staff only content')){
      $role_options[] = $role->id();
    }
  }

  /** @var \Drupal\node\NodeTypeInterface $type */
  $type = $form_state->getFormObject()->getEntity();
  
  $form['#attached']['library'][] = 'fontanalib/admin';
  $form['#attached']['library'][] = 'fontanalib/vertical-tabs';

  $form['fontanalib'] = [
    '#type' => 'details',
    '#title' => t('Access Control'),
    '#weight' => 35,
    '#group' => 'additional_settings',
  ];
  // View Options
  $form['fontanalib']['view'] = [
    '#type' => 'details',
    '#title' => t('Viewing'),
    '#weight' => 1,
    '#group' => 'fontanalib',
    '#open' => TRUE,
  ];
  $form['fontanalib']['view']['fontanalib_publish_public'] = [
    '#type' => 'checkbox',
    '#title' => t('Allow staff to overide viewing access to this content type.'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'publish_public', $config->get('default_publish_public')),
  ];
  $form['fontanalib']['view']['fontanalib_viewing_user'] = [
    '#type'                    => 'entity_autocomplete',
    '#target_type'             => 'user',
    '#title'                   => t('Extend viewing to allowed users'),
    '#description'             => t('Enter a comma-separated list of non-staff user names who will be able to view content of this type.'),
    '#value'                   => $type->getThirdPartySetting('fontanalib', 'viewing_user', ''),
    '#size'                    => 60,
    '#maxlength'               => FALSE,
    '#selection_settings' => [
      'include_anonymous' => TRUE,
      'filter' => [
        'role' => $role_options,
      ],
    ],
    '#weight'                  => -10,
    '#prefix' => '<div id="edit-fontanalib_editing_user">',
    '#suffix' => '</div>',
  ];
  $options = [
    'und'           => 'Unchanged from default',
    'staff'         => 'Staff Only',
    'public'        => 'Publicly Published'
  ];
  $form['fontanalib']['view']['fontanalib_view_access'] = [
    '#type' => 'select',
    '#title' => t('Default view access for this type.'),
    '#options' => $options,
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'view_access', $config->get('default_view_access')),
  ];
  
  $form['fontanalib']['fontanalib_edit_access_enable'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable edit access configuration for this content type'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'edit_access_enable', $config->get('default_edit_access_enable')),
  ];
  // Publishing options.
  $form['fontanalib']['editing'] = [
    '#type' => 'details',
    '#title' => t('Editing'),
    '#weight' => 1,
    '#group' => 'fontanalib',
    '#open' => TRUE,
    '#states' => [
      'visible' => [
        ':input[name="fontanalib_edit_access_enable"]' => ['checked' => TRUE],
      ],
    ],
  ];
  
  $form['fontanalib']['editing']['fontanalib_editing_allow_only_staff'] = [
    '#type' => 'checkbox',
    '#title' => t('Allow staff to configure edit permissions for this content.'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'editing_allow_only_staff', $config->get('default_edit_access_enable')),
    '#ajax' => array(
      'callback' => 'fontanalib_allow_only_staff_change',
      'wrapper' =>'edit-fontanalib_editing_user',
      'event' => 'change',
    ),
  ];
  $form['fontanalib']['editing']['advanced'] = [
    '#type' => 'details',
    '#title' => t('Advanced options'),
    '#open' => FALSE,
  ];
  $form['fontanalib']['editing']['advanced']['fontanalib_editing_allow_county'] = [
    '#type' => 'checkbox',
    '#title' => t('Allow staff from Counties related to this content to edit this content.'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'editing_allow_county', $config->get('default_edit_access_enable')),
  ];
  $form['fontanalib']['editing']['advanced']['fontanalib_editing_allow_library'] = [
    '#type' => 'checkbox',
    '#title' => t('Allow staff from Libraries related to this content to edit this content.'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'editing_allow_library', $config->get('default_edit_access_enable')),
  ];
  $form['fontanalib']['editing']['advanced']['fontanalib_editing_allow_department'] = [
    '#type' => 'checkbox',
    '#title' => t('Allow staff from Departments related to this content to edit this content.'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'editing_allow_department', $config->get('default_edit_access_enable')),
  ];
  $form['fontanalib']['editing']['advanced']['fontanalib_editing_allow_workgroup'] = [
    '#type' => 'checkbox',
    '#title' => t('Allow staff from Committees/Workgroups related to this content to edit this content.'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'editing_allow_workgroup', $config->get('default_edit_access_enable')),
  ];
  $form['fontanalib']['editing']['advanced']['fontanalib_editing_and_or'] = [
    '#type' => 'checkbox',
    '#title' => t('Limit editing to staff matching both library and department criteria'),
    '#default_value' => $type->getThirdPartySetting('fontanalib', 'editing_and_or', $config->get('default_edit_access_enable')),
    '#states' => [
      'visible' => [
        ':input[name="fontanalib_editing_allow_library"]' => ['checked' => TRUE],
        ':input[name="fontanalib_editing_allow_department"]' => ['checked' => TRUE],
        ':input[name="fontanalib_editing_allow_county"]' => ['checked' => FALSE],
      ],
    ],
  ];
  /**
   * IF it is limited to staff.. then we need to get a list of
   * staff roles (from somewhere) and use that to filter selection
   * settings.... use ajax / whatevs
   */

  $form['fontanalib']['editing']['fontanalib_editing_user'] = [
    '#type'                    => 'entity_autocomplete',
    '#target_type'             => 'user',
    '#title'                   => t('Limit editing to allowed users'),
    '#description'             => t('Enter a comma-separated list of user names who will be able to edit content of this type.'),
    '#value'                   => $type->getThirdPartySetting('fontanalib', 'editing_user', ''),
    '#size'                    => 60,
    '#maxlength'               => FALSE,
    '#selection_settings' => [
      'include_anonymous' => FALSE,
    ],
    '#weight'                  => -10,
    '#prefix' => '<div id="edit-fontanalib_editing_user">',
    '#suffix' => '</div>',
  ];

  if($form_state->getValue('fontanalib_editing_allow_only_staff') == 1){
    $form['fontanalib']['editing']['fontanalib_editing_user']['#selection_settings']['filter'] = [
      'role' => $config->get('staff_roles'),
    ];
  } else{
    $form['fontanalib']['editing']['fontanalib_editing_user']['#selection_settings']=[];
  }
  $form['#entity_builders'][] = 'fontanalib_form_node_type_form_builder';
  
  // $vocabularies = Vocabulary::loadMultiple();
  // $option = array();
  // if($vocabularies){
  //   foreach($vocabularies as $vid => $vocab){
  //     $option[$vid] = $vocab->get('name');
  //   }
  // }
}

/**
 * Entity builder for the node type form with fontanalib options.
 */
function fontanalib_form_node_type_form_builder($entity_type, NodeTypeInterface $type, &$form, FormStateInterface $form_state) {
  $type->setThirdPartySetting('fontanalib', 'publish_public', $form_state->getValue('fontanalib_publish_public'));
  $type->setThirdPartySetting('fontanalib', 'edit_access_enable', $form_state->getValue('fontanalib_edit_access_enable'));
  if($form_state->getValue('fontanalib_edit_access_enable') == 1){
    // $type->setThirdPartySetting('fontanalib', 'editing_allow_only_staff', $form_state->getValue('fontanalib_editing_allow_only_staff'));
    $type->setThirdPartySetting('fontanalib', 'editing_allow_county', $form_state->getValue('fontanalib_editing_allow_county'));
    $type->setThirdPartySetting('fontanalib', 'editing_allow_library', $form_state->getValue('fontanalib_editing_allow_library'));
    $type->setThirdPartySetting('fontanalib', 'editing_allow_department', $form_state->getValue('fontanalib_editing_allow_department'));
    $type->setThirdPartySetting('fontanalib', 'editing_allow_workgroup', $form_state->getValue('fontanalib_editing_allow_workgroup'));
    $type->setThirdPartySetting('fontanalib', 'editing_and_or', $form_state->getValue('fontanalib_editing_and_or'));
    $type->setThirdPartySetting('fontanalib', 'editing_user', $form_state->getValue('fontanalib_editing_user'));
  }
}
function fontanalib_allow_only_staff_change(array $form, FormStateInterface $form_state){
  return $form['fontanalib']['editing']['fontanalib_editing_user'];
}
