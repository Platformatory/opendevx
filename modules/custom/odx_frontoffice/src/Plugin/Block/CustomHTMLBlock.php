<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom html block.
 *
 * @Block(
 *   id = "odx_frontoffice_custom_html",
 *   admin_label = @Translation("Custom HTML"),
 *   category = @Translation("ODX Frontoffice")
 * )
 */
class CustomHTMLBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'html' => $this->t('Paste your custom HTML here.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['html'] = [
      '#type' => 'textarea',
      '#title' => $this->t('html'),
      '#rows' => 50,
      '#resizable' => 'horizontal',
      '#default_value' => $this->configuration['html'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['html'] = $form_state->getValue('html');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#theme' => 'custom_html_block',
      '#custom_html' => $this->configuration['html'],
    ];
    return $build;
  }

}
