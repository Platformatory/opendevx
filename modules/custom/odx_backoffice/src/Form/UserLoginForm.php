<?php

namespace Drupal\odx_backoffice\Form;

use Drupal\user\Form\UserLoginForm as CoreUserLoginForm;
use Drupal\Core\Form\FormStateInterface;


class UserLoginForm extends CoreUserLoginForm {
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('odx_backoffice.dashboard');
  }
}
