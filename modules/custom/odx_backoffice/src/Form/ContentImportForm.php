<?php

namespace Drupal\odx_backoffice\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ODX Backoffice settings for this site.
 */
class ContentImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odx_backoffice_content_import';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['odx_integrations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import_tarball'] = [
      '#type' => 'file',
      '#title' => $this->t('Content archive'),
      '#description' => $this->t('Allowed types: @extensions.', ['@extensions' => 'tar.gz tgz tar.bz2']),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#disabled' => !$directory_is_writable,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['import_tarball'])) {
      $file_upload = $all_files['import_tarball'];
      if ($file_upload->isValid()) {
        $form_state->setValue('import_tarball', $file_upload->getRealPath());
        return;
      }
    }
    $form_state->setErrorByName('import_tarball', $this->t('The file could not be uploaded.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Your content files were successfully uploaded'));
    $form_state->setRedirect('odx_backoffice.dashboard');
  }

}
