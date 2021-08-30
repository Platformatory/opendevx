<?php

namespace Drupal\odx_frontoffice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\views\Views;

/**
 * Returns responses for ODX Frontoffice routes.
 */
class OdxFrontofficeGlobalsController extends ControllerBase {

  public function globalEvents() {
    $build['content'] = views_embed_view('events', 'end_user');
    return $build;
  }

  /**
   * /apps.
   */
  public function apps() {
    $build['breadcrumb'] = [
      '#theme' => 'generic_breadcrumb',
      '#entity' => $this->t('Apps'),
    ];
    $build['content'] = $this->buildRenderArray('my_apps', 'user_block');
    $build['content']['#attached']['library'][] = 'opendevx_theme/jquerymodal';
    return $build;
  }

  /**
   * /subscriptions.
   */
  public function subscriptions() {
    $build['breadcrumb'] = [
      '#theme' => 'generic_breadcrumb',
      '#entity' => $this->t('Subscriptions'),
    ];

    $subscriptions = $this->getCurrentUserSubscriptions();
    $build['subscriptions'] = [
      '#theme' => 'subscriptions_table',
      '#subscriptions' => $subscriptions,
      '#prefix' => '<div class="container px-5 py-5 mx-auto">',
      '#suffix' => '</div>',
  ];
    $build['subscriptions']['#attached']['library'][] = 'opendevx_theme/jquerymodal';
    $build['#cache']['max-age'] = 0;
    return $build;
  }


  protected function getCurrentUserSubscriptions() {
    $nids = \Drupal::entityQuery('node')->condition('type','subscription')->condition('uid', 1)->execute();
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
    $subscriptions = [];
    foreach ($nodes as $node) {
      $app = $node->get('applications')->first()->get('entity')->getTarget()->getValue();
      $billing = _odx_monetization_compute_billing($node);
      $past_invoices = $this->getPastInvoices($node);
      $subscriptions[] = [
        'name' => $app->label(),
        'start' => $node->start_date->date->format('jS M Y'),
        'end' => $node->end_date->date->format('jS M Y'),
        'current_bill' => $billing['total'],
        'url' => $node->toUrl()->toString(),
      ];
    }
    return $subscriptions;
  }

  protected function getPastInvoices(\Drupal\Node\NodeInterface $node) {
    $past_invoices = [];
    $vids = \Drupal::service('entity_type.manager')->getStorage('node')->revisionIds($node);
    $revisions = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultipleRevisions($vids);
    foreach ($revisions as $revision) {
      $billing = _odx_monetization_compute_billing($revision);
      if ($billing['li']) {
        $past_invoices[] = [
            '#theme' => 'invoice',
            '#invoice' => $billing,
            '#start_date' => $revision->start_date->date->format('jS M Y'),
            '#end_date' => $revision->end_date->date->format('jS M Y'),
            '#prefix' => '<div class="container px-5 py-5 mx-auto flex flex-wrap"><div class="flex-1 truncate">',
            '#suffix' => '</div></div>',
        ];
      }
    }
    return $past_invoices;
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
