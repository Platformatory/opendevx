<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a hero with 3 blocks block.
 *
 * @Block(
 *   id = "odx_frontoffice_hero_with_3_blocks_configurable",
 *   admin_label = @Translation("Hero with 3 blocks"),
 *   category = @Translation("ODX Frontoffice")
 * )
 */
class HeroWith3BlocksBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'cta_title' => $this->t('One API platform for Multiple Data Sources'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['cta_title'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CTA Title'),
      '#default_value' => $this->configuration['cta_title'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['cta_title'] = $form_state->getValue('cta_title');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#theme' => 'hero_with_3_blocks',
      '#cta_title' => $this->configuration['cta_title'],
    ];
    return $build;
  }

}
