<?php

namespace Drupal\fontanalib\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure user delete reassign settings for this site.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fontanalib_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fontanalib.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fontanalib.settings');
    $content_entities = !$config || !$config->get('entities') ? ['node'] : $config->get('entities');

    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    $role_options = [];
    foreach($roles as $role){
      $role_options[$role->id()] = $role->label();
    }
    $form['#tree'] = TRUE;
    $form['user_settings'] = array(
      '#type'  => 'details',
      '#title' => 'User settings',
      '#open' => TRUE,
      '#weight'=>-5,
    );

    $form['user_settings']['role_content_assign'] = array(
      '#tree'   => TRUE,
      '#type'  => 'fieldset',
      '#title' => 'Account Cancellation',
    );

    foreach($content_entities as $entity){
      $form['user_settings']['role_content_assign'][$entity] = [
        '#type' => 'checkboxes',
        '#title' => "Select the user role you want to filter on for re-assigning $entity entities on account cancellation.",
        '#default_value' => $config->get('role_content_assign') && $config->get('role_content_assign')[$entity] ? $config->get('role_content_assign')[$entity] : [],
        '#options' => $role_options,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // Retrieve the configuration.
    //$config = $this->configFactory->getEditable('fontanalib.settings');
    $config = $this->config('fontanalib.settings');
    $entities = !$config || !$config->get('entities') || !is_array($config->get('entities')) ? ['node'] : $config->get('entities');


    //$roleConfig = [];
    foreach($entities as $entity){
      $roleConfig[$entity] = $form_state->getValue(['user_settings', 'role_content_assign', $entity]);
      //$roleConfig = $form_state->getValue(['user_settings', 'role_content_assign', $entity]);
      \Drupal::logger('fontanalib')->notice('config @entity <br/><pre>@type</pre>',
        array(
            '@type' => print_r($roleConfig, TRUE),
            '@entity'=> $entity,
        ));
    }

    // Set the submitted configuration setting.
    $config
      ->set('role_content_assign', $roleConfig)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
