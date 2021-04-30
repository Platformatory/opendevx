<?php

namespace Drupal\odx_apilm_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a ODX Apilm Manager form.
 */
class SyncConfigurationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odx_apilm_manager_sync_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $repository = NULL) {

    $metadata = json_decode($repository->internal_metadata->value);
    $sync_dir = $metadata->run_dir . '/runs/';
    $last_run = $metadata->last_run;
    $runs = array_diff(scandir($sync_dir), array('..', '.'));

    $form['force_sync'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('This resets the checksum therefore forcing an update with the API Lifecycle Manager'),
      '#title' => $this->t('Force Sync'),
    ];

    $form['restore'] = [
      '#type' => 'select',
      '#description' => $this->t('Select a version to restorer'),
      '#title' => $this->t('Version'),
      '#options' => $runs,
      ''
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (mb_strlen($form_state->getValue('message')) < 10) {
      $form_state->setErrorByName('name', $this->t('Message should be at least 10 characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

}
