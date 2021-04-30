<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a hero with sign up block.
 *
 * @Block(
 *   id = "odx_frontoffice_hero_with_sign_up",
 *   admin_label = @Translation("Hero with sign up"),
 *   category = @Translation("ODX Frontoffice")
 * )
 */
class HeroWithSignUpBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#theme' => 'hero_with_signup',
    ];
    return $build;
  }

}
