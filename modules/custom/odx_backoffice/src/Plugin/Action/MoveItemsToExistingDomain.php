<?php

namespace Drupal\odx_backoffice\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a a Move items to existing domain action.
 *
 * @Action(
 *   id = "odx_backoffice_move_items_to_existing_domain",
 *   label = @Translation("Move items to existing domain"),
 *   type = "node",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class MoveItemsToExistingDomain extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['title' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#title' => $this->t('New title'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['title'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
  }

  /**
   * {@inheritdoc}
   */
  public function access($node, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $node */
    $access = $node->access('update', $account, TRUE)
      ->andIf($node->title->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    /** @var \Drupal\node\NodeInterface $node */
    $node->setTitle($this->configuration['title'])->save();
  }

}
