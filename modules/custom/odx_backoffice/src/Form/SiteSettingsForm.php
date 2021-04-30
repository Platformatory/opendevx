<?php

namespace Drupal\odx_backoffice\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ODX Backoffice settings for this site.
 */
class SiteSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odx_backoffice_site_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.site'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    ];
    $form['site_mail'] = [
      '#type' => 'email',
      '#title' => t('Email address'),
      '#default_value' => $site_mail,
      '#description' => t("The <em>From</em> address in automated emails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this email being flagged as spam.)"),
      '#required' => TRUE,
    ];

    $form['site_footer'] = [
      '#type' => 'textarea',
      '#title' => t('Footer'),
      '#default_value' => $this->config('system.site')->get('site_footer'),
      '#description' => t('Enter HTML for the global footer. Useful for global text and links'),
    ];   
           

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  $this->config('system.site')
      ->set('name', $form_state->getValue('site_name'))
      ->set('mail', $form_state->getValue('site_mail'))
      ->set('site_footer', $form_state->getValue('site_footer'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
