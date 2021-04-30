<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a product benefits block block.
 *
 * @Block(
 *   id = "odx_frontoffice_product_benefits_block",
 *   admin_label = @Translation("Product Benefits Block"),
 *   category = @Translation("ODX Frontoffice")
 * )
 */
class ProductBenefitsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $rendered_benefits = [];
    $benefits = $node->benefits->referencedEntities();
    foreach($benefits as $benefit) {
      $rendered_benefits[] = [
        'description' => strip_tags($benefit->description->value),
        'name' => $benefit->name->value,
        'icon' => $benefit->icon->value,
      ];
    }
    $build['content'] = [
      '#theme' => 'product_benefits_block',
      '#benefits' => $rendered_benefits,
    ];
    $build['content']['#attached']['library'][] = 'lp_fontawesome/fontawesome';
    return $build;
  }

}
