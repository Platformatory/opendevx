<?php

namespace Drupal\odx_forms\Controller;

use Drupal\odx_backoffice\Controller\OdxBaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for ODX forms routes.
 */
class OdxFormsController extends OdxBaseController {


  /**
   * Builds the title.
   */
  public function title($entity_type) {
    return 'Add ' . $this->getEntityTypeLabel($entity_type);
  }

  protected function getEntityTypeLabel($entity_type) {
    if ($entity_type == 'domain') {
      $entity = 'group_type';
    } else {
      $entity = 'node_type';
    }
    $entity_type_info = $this->entityTypeManager()->getStorage($entity)->load($entity_type);
    return $entity_type_info->label();
  }

  /**
   * Builds the response.
   */
  public function build($entity_type) {
    //TODO: Allow only a fixed set of entities here.
    if ($entity_type == 'domain') {
      $entity = 'group';
    } else {
      $entity = 'node';
    }
    $odx_entity = $this->entityTypeManager()->getStorage($entity)->create([
      'type' => $entity_type,
    ]);
    if ($odx_entity) {
      $build['form'] = $this->entityFormBuilder()->getForm($odx_entity,'odx');
      return $build;
    } else {
      throw new NotFoundHttpException();
    }
  }
}
