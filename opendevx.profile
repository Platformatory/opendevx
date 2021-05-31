<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function opendevx_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $form['site_information']['site_name']['#default_value'] = 'Acme Developer eXchange';
  $form['register_hub'] = array(
    '#type' => 'checkbox',
    '#title' => t('Register with OpenDevX hub'),
    '#default_value' => true,
    '#description' => t('Registers your developer exchange with OpenDevX hub. This enables seamless upgrades to OpenDevX enterprise. when checking for updates, information about your site is sent to opendevx.io'), 
  );
  $form['#submit'][] = 'opendevx_install_configure_submit';
}

function opendevx_install_configure_submit($form, FormStateInterface $form_state) {
  $config = \Drupal::service('config.factory')->getEditable('system.site');
  $config->set('register_hub', $form_state->getValue('register_hub'))->save();
}

/**
 * Implements hook_toolbar().
 */
function opendevx_toolbar() {
  $items['experimental-profile-warning'] = [
    '#type' => 'toolbar_item',
    'tab' => [
      '#type' => 'inline_template',
      '#template' => '<a class="toolbar-warning" href="{{ more_info_link }}">This installation is for demonstration purposes only.</a>',
      '#context' => [
        'more_info_link' => 'https://www.drupal.org/project/drupal/issues/2829101',
      ],
      '#attached' => [
        'library' => ['opendevx/toolbar-warning'],
      ],
    ],
    '#weight' => 999,
  ];
  return $items;
}
