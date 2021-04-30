<?php

namespace Drupal\odx_forms\Controller;

use Drupal\odx_backoffice\Controller\OdxBaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Returns responses for ODX forms routes.
 */
class OdxFormsEditController extends OdxBaseController {

  /**
   * Builds the response.
   */
  public function build($entity_type, $uuid) {
    // TODO: more error checking, like 404 and 403, allow only specific CTs.
    if ($entity_type == 'domain') {
      $entity = \Drupal::service('entity_type.manager')->getStorage('group')
        ->loadByProperties(['uuid' => $uuid, 'type' => 'domain']);
    } else {
      $entity = $this->entityTypeManager->getStorage('node')
        ->loadByProperties(['uuid' => $uuid, 'type' => $entity_type]);
    }
    $entity = reset($entity);
    if ($entity) {
      $build['#title'] = 'Edit ' . $entity->label();
      $build['form'] = $this->entityFormBuilder()->getForm($entity, 'odx');
      return $build;
    }
    else {
      throw new NotFoundHttpException();
    }
  }
}
