<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a product features block block.
 *
 * @Block(
 *   id = "odx_frontoffice_product_features_block",
 *   admin_label = @Translation("Product Features Block"),
 *   category = @Translation("ODX Frontoffice"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:product",
 *       label = @Translation("Current Node"),
 *       required = TRUE,
 *     )
 *   }
 * )
 */
class ProductFeaturesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $build = [];
    $rendered_features = [];
    $features = $node->features->referencedEntities();
    foreach($features as $feature) {
      $rendered_features[] = [
        'description' => strip_tags($feature->description->value),
        'name' => $feature->name->value,
        'icon' => $feature->icon->value,
      ];
    }
    $apis = $node->get('apis')->referencedEntities();
      $api = reset($apis);
      $try_url = $node->toUrl()->toString() . $api->toUrl()->toString() . '/try';
      $browse_url = $node->toUrl()->toString() . $api->toUrl()->toString() . '/browse';
      $build['api'] = [
        '#theme' => 'product_api',
        '#try_url' => $try_url,
        '#browse_url' => $browse_url,
      ];
    $build['content'] = [
      '#theme' => 'product_features_block',
      '#features' => $rendered_features,
    ];
    $build['content']['#attached']['library'][] = 'lp_fontawesome/fontawesome';
    return $build;
  }

}
