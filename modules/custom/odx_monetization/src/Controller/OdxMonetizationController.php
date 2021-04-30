<?php

namespace Drupal\odx_monetization\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for ODX Monetization routes.
 */
class OdxMonetizationController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
