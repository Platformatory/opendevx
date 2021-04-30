<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a product hero 1 block.
 *
 * @Block(
 *   id = "odx_frontoffice_product_hero_1",
 *   admin_label = @Translation("Product Hero 1"),
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
class ProductHero1Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if ($node->hero_image->entity->field_media_image->entity) {
      $image_url = file_create_url($node->hero_image->entity->field_media_image->entity->getFileUri());
    } else {
      $image_url = 'https://images.unsplash.com/photo-1486325212027-8081e485255e?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80';
    }
    $rendered_kpis = [];
    $kpis = $node->kpis->referencedEntities();
    foreach($kpis as $kpi) {
      $rendered_kpis[] = ['description' => strip_tags($kpi->description->value), 'stat' => $kpi->stat->value];
    }
    $build['content'] = [
      '#theme' => 'product_hero_1',
      '#image_url' => $image_url,
      '#kpis' => $rendered_kpis,
      '#description' => $node->description->value,
      '#cta_link' => '/apps/create',
      '#dest' => \Drupal::service('path.current')->getPath(),

    ];
    return $build;
  }

}
