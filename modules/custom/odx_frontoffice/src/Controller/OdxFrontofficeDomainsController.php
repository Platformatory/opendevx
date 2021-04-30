<?php

namespace Drupal\odx_frontoffice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\views\Views;

/**
 * Returns responses for ODX Frontoffice routes.
 */
class OdxFrontofficeDomainsController extends ControllerBase {

  /**
   * domain/%/products.
   */
  public function products($group) {
    $build['breadcrumb'] = [
      '#theme' => 'domain_breadcrumb',
      '#label' => $group->label(),
      '#url' => $group->toUrl()->toString(),
      '#entity' => $this->t('Products'),
    ];
    $build['content'] = views_embed_view('products', 'domain', $group->id());
    return $build;
  }

  /**
   * domain/%/use-cases.
   */
  public function usecases($group) {
    $build['breadcrumb'] = [
      '#theme' => 'domain_breadcrumb',
      '#label' => $group->label(),
      '#url' => $group->toUrl()->toString(),
      '#entity' => $this->t('Use Cases'),
    ];
    $build['content'] = $this->buildRenderArray('use_cases', 'domain');
    return $build;
  }

  /**
   * domain/%/docs.
   */
  public function docs($group) {
    $build['breadcrumb'] = [
      '#theme' => 'domain_breadcrumb',
      '#label' => $group->label(),
      '#url' => $group->toUrl()->toString(),
      '#entity' => $this->t('Docs'),
    ];
    $build['content'] = $this->buildRenderArray('docs', 'end_user');
    return $build;
  }

  // TODO: should probably be a ControllerTrait
  protected function buildRenderArray($view_id, $display_id) {
    return [
      'content' =>  [
        '#type' => 'block',
        'content' => [
        'system_main' => views_embed_view($view_id, $display_id),
        ],
        '#prefix' => '<div class="container px-5 py-5 mx-auto flex flex-wrap">',
        '#suffix' => '</div>',
      ],
    ];
  }
}
