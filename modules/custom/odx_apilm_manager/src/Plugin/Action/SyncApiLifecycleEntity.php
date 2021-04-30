<?php

namespace Drupal\odx_apilm_manager\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a a Sync API Lifecycle Entity action.
 *
 * @Action(
 *   id = "odx_apilm_manager_sync_api_lifecycle_entity",
 *   label = @Translation("Sync API Lifecycle Entity"),
 *   type = "node",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class SyncApiLifecycleEntity extends ActionBase {

  protected $apilm;

  /**
   * {@inheritdoc}
   */
  public function access($node, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $node */
    $access = $node->access('update', $account, TRUE)
      //->andIf(in_array($node->getType(), array('api', 'product')))
      //->andIf(!empty($node->source->value))
      //->andIf(!empty($node->rid->value))
      ->andIf($node->title->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    /** @var \Drupal\node\NodeInterface $node */
    $repository = $node->get('source')->first()->get('entity')->getTarget()->getValue();
    $this->apilm = odx_apilm_manager_service_instance($repository);
    $remote = (object) $this->pull($node->rid->value, $node->env->value);
    $this->save($node, $remote);
    \Drupal::logger('APISyncAction')->notice('<pre>'. print_r($remote, true) . '</pre>');

    //$node->setTitle($this->t('New title'))->save();
  }

  protected function pull($rid, $env) {
    return $this->apilm->fetchApi($rid, $env);
  }

  protected function save($node, $remote) {
    $node->set('rid', $remote->rid);
    $node->set('title', $remote->title);
    $node->set('description', $remote->description);
    $node->set('env', $remote->env);
    $node->set('spec', $remote->spec);
    $node->set('api_type', $remote->api_type);
    $node->set('source', $remote_nid);
    $node->set('internal_metadata', json_encode($remote->internal_metadata));
    $node->set('remote', json_encode($remote->remote));
    $node->save();
  }

}
