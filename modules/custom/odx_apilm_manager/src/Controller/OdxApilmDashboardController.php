<?php

namespace Drupal\odx_apilm_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Returns responses for ODX Apilm Manager routes.
 */
class OdxApilmDashboardController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build($nid) {

    //$current_path = \Drupal::service('path.current')->getPath();
    //$path_args = explode('/', $current_path);
    $node = Node::load($nid);
    $form_class = '\Drupal\odx_apilm_manager\Form\SyncConfigurationForm';
    $build['form'] = \Drupal::formBuilder()->getForm($form_class, $node);
    //$build['view'] = views_embed_view('apis', 'admin_apis_by_repository', $nid);
    return $build;
  }

}
