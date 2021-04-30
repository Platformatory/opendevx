<?php

namespace Drupal\odx_frontoffice\Form;

use Drupal\user\Form\UserLoginForm as CoreUserLoginForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class UserLoginForm extends CoreUserLoginForm {
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $redirect_url = Url::fromUserInput('/apps');
    $form_state->setRedirectUrl($redirect_url);
  }
}
