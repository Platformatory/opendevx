<?php

namespace Drupal\odx_frontoffice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for ODX Frontoffice routes.
 */
class OdxFrontofficeProductsController extends ControllerBase {

  /**
   * /product/{product}/api.
   */
  public function api($product) {
    $apis = $product->get('apis')->referencedEntities();
    $api = reset($apis);
    $build['navigation'] = [
      '#theme' => 'product_navigation',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#api_count' => count($apis),
      '#try_url' => $product->toUrl()->toString() . $api->toUrl()->toString() . '/try',
      '#browse_url' => $product->toUrl()->toString() . $api->toUrl()->toString() . '/browse',
    ];
    $build['breadcrumb'] = [
      '#theme' => 'product_breadcrumb',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#entity' => $this->t('APIs'),
    ];

    $display_settings = [
      'label' => 'hidden',
      'type' => 'entity_reference_entity_view',
      'settings' => [
        'view_mode' => 'teaser',
        'link' => FALSE,
      ],
    ];

    $build['content'] = $product->apis->view($display_settings);

    return $build;
  }

  /**
   * /product/{product}/docs.
   */
  public function docs($product) {
    $apis = $product->get('apis')->referencedEntities();
    $api = reset($apis);
    $build['navigation'] = [
      '#theme' => 'product_navigation',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#api_count' => count($apis),
      '#try_url' => $product->toUrl()->toString() . $api->toUrl()->toString() . '/try',
      '#browse_url' => $product->toUrl()->toString() . $api->toUrl()->toString() . '/browse',
    ];
    $build['breadcrumb'] = [
      '#theme' => 'product_breadcrumb',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#entity' => $this->t('Docs'),
    ];

    $book =  $product->docs->entity;
    if (!$book) {
      throw new NotFoundHttpException();
    }
    $build['content'] = \Drupal::service('entity_type.manager')->getViewBuilder('node')->view($book);

    return $build;
  }

  /**
   * /product/{product}/pricing.
   */
  public function pricing($product) {
    $apis = $product->get('apis')->referencedEntities();
    $api = reset($apis);
    $build['navigation'] = [
      '#theme' => 'product_navigation',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#api_count' => count($apis),
      '#try_url' => $product->toUrl()->toString() . $api->toUrl()->toString() . '/try',
      '#browse_url' => $product->toUrl()->toString() . $api->toUrl()->toString() . '/browse',
    ];
    $plans = \Drupal\node\Entity\Node::loadMultiple([9]);
    $view_build = \Drupal::entityTypeManager()->getViewBuilder('node');
    $plan = $view_build->view(reset($plans), 'full');
    $build['products'] = $plan;
    return $build;
  }

  protected function getPlans(\Drupal\node\NodeInterface $product) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'plan')
      ->condition('products', $product->id());
    $nids = $query->execute();
    $plan_nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    return $plan_nodes;
  }
}
