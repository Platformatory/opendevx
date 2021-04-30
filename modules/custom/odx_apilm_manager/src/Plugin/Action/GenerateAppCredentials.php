<?php

namespace Drupal\odx_apilm_manager\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a a Generate App Credentials action.
 *
 * @Action(
 *   id = "odx_apilm_manager_generate_app_credentials",
 *   label = @Translation("Generate App Credentials"),
 *   type = "node",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class GenerateAppCredentials extends ActionBase {

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
    $credentials = $this->generate_credentials($node);
    $this->save($node, $credentials);
  }

  protected function generate_credentials($node) {
    $product = $node->get('products')->first()->get('entity')->getTarget()->getValue();
    $grouping = _get_api_groups_by_product($product); // move this to the class
    $app_metadata = _odx_apilm_app_metadata($node); // move this here
    $credentials = array();
    if(!empty($node->credentials->value)) {
      $credentials = json_decode($node->credentials->value);
    }
    foreach($grouping as $repo_id => $group) {
      // TODO: replace this with a plugin manager managed instance
      $apilm = odx_apilm_manager_service_instance($group['repository']);
      foreach($group['apis'] as $index => $api) {
        $app_metadata['apis'][] = array('metadata' => json_decode($api->internal_metadata->value, JSON_OBJECT_AS_ARRAY), 'rid' => $api->rid->value, 'env' => $api->env->value);
        $apis[] = $api->id();
      }
        // Create the app
      $credentials[] = array(
        'credentials' => $apilm->createApp($app_metadata),
        'source' => $repo_id,
        'valid_for' => $apis
      );
    }
    return $credentials;
    // Stash credentials into the JSON store
  }

  protected function save($node, $credentials) {
    $node->set('credentials', json_encode($credentials, JSON_PRETTY_PRINT));
    $node->save();
  }

}
