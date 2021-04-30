<?php

namespace Drupal\odx_integrations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ODX Hub settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odx_integrations_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['odx_integrations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $endpoint = $this->config('odx_integrations.settings')->get('endpoint');
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('ODX Hub Endpoint'),
      '#default_value' => $endpoint?$endpoint:"https://www.odxhub.com",
    ];
    $form['organization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization'),
      '#default_value' => $this->config('odx_integrations.settings')->get('organization'),
    ];
    $form['access_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Access Key'),
      '#default_value' => $this->config('odx_integrations.settings')->get('access_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: make odx hub API call here.
    // if ($form_state->getValue('example') != 'example') {
    //   $form_state->setErrorByName('example', $this->t('The value is not correct.'));
    // }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('odx_integrations.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('organization', $form_state->getValue('organization'))
      ->set('access_key', $form_state->getValue('access_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
