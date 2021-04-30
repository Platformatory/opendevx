<?php

namespace Drupal\odx_backoffice_guide\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for ODX Backoffice Guide routes.
 */
class OdxBackofficeGuideController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    $form_class = 'Drupal\checklistapi\Form\ChecklistapiChecklistForm';
    $build['form'] = \Drupal::formBuilder()->getForm($form_class, "backoffice_checklist");
    return $build;
  }

}
