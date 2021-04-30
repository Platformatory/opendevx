<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

/**
 * Provides an user nav block.
 *
 * @Block(
 *   id = "odx_frontoffice_user_nav_mobile",
 *   admin_label = @Translation("User Nav Mobile"),
 *   category = @Translation("Platformatory")
 * )
 */
class MobileUserNavBlock extends UserNavBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = [
      '#theme' => 'user_nav_mobile',
      '#menu' => $this->getMenu(),
    ];
    return $build;
  }

}
