<?php

namespace Drupal\odx_apilm_manager\Controller;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\odx_backoffice\Controller\OdxBaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use \Drupal\node\Entity\Node;

use Drupal\odx_apilm_manager\Connector;

/**
 * Returns responses for ODX Apilm Manager routes.
 */
class OdxApilmManagerController extends OdxBaseController {

  /**
   * Builds the response.
   */
  public function sync($uuid) {
    //$apilm = \Drupal::service('plugin.manager.api_lifecycle_provider')->createInstance('AwsApiGateway');
    // $entity will be your APILM node.
    $entity = $this->entityTypeManager->getStorage('node')->loadByProperties(['uuid' => $uuid]);
    $entity = reset($entity);
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    // TODO: replace this with a plugin manager managed instance
    $apilm = odx_apilm_manager_service_instance($entity);

    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Synchronizing APIs...'))
      ->setFinishCallback(__CLASS__.'::finished')
      ->setInitMessage(t('Beginning API Synchronization Process...'));

    $batch_builder
        ->addOperation(__CLASS__.'::bootstrap', [$apilm])
        ->addOperation(__CLASS__.'::process')
        ->addOperation(__CLASS__.'::delta_compute') // add a new phase here to compute just new items and items that have changed
        //->addOperation(__CLASS__.'::update_existing) // add a new phase here to compute just new items and items that have changed
        ->addOperation(__CLASS__.'::save')
        ->addOperation(__CLASS__.'::transform')
        ->addOperation(__CLASS__.'::writeTransform')
        ->addOperation(__CLASS__.'::import')
        ->addOperation(__CLASS__.'::update_repository', [$entity]);

    batch_set($batch_builder->toArray());
    return batch_process('user');
  }

  static function bootstrap($apilm, &$context) {
    $context['message'] = t('Initializing import and connecting to the gateway..');
    $context['results']['class'] = $apilm; // stash
  }

  static function process(&$context) {
    $context['message'] = t('Fetching APIs and building the store..');
    $apilm = $context['results']['class'];
    $apilm->fetchSync();
    $context['results']['class'] = $apilm; // stash
  }

  static function delta_compute(&$context) {
    $context['message'] = t('Comparing the last known checksum to compute deltas..');
    $apilm = $context['results']['class'];
    $apilm->diffLastRun();
    $context['results']['class'] = $apilm; // stash
  }

  static function save(&$context){
    $apilm = $context['results']['class'];
    if($apilm->getDiff()) {
      $context['message'] = t('Writing files to the state store..');
      $apilm->saveTree();
    } else {
      $context['message'] = t('No change since last run. Skipping saving files to the state store..');
    }
    $context['results']['class'] = $apilm; // stash
  }

  static function transform(&$context){
    $apilm = $context['results']['class'];
    if($apilm->getDiff()) {
      $context['message'] = t('Now transforming rum yields to ODX format..');
      $apilm->generateOdxOutput();
    } else {
      $context['message'] = t('No change since last run. Skipping transformation..');
    }
    $context['results']['class'] = $apilm; // stash
  }

  static function writeTransform(&$context){
    $apilm = $context['results']['class'];
    if($apilm->getDiff()) {
      $context['message'] = t('Now writing ODX artefacts..');
      $apilm->generateOdxArtefacts();
    } else {
      $context['message'] = t('No change since last run. Skipping transformation..');
    }
    $context['results']['class'] = $apilm; // stash
  }

  static function import(&$context) {
    $apilm = $context['results']['class'];
    if($apilm->getDiff()) {
      $context['message'] = t('Now importing changes into ODX ..');
      //$apis = //array_diff(scandir($apilm->getRunDir()), array('.','..', 'yield.json'));
      $apis = preg_grep('~^api.*\.json$~', scandir($apilm->getRunDir()));
      $products = preg_grep('~^product.*\.json$~', scandir($apilm->getRunDir()));

      // Following parts do some crazy batch manipulation to append to the current queue
      $batch = &batch_get();
      $batch_next_set = $batch['current_set'] + 1;
      $batch_set = &$batch['sets'][$batch_next_set];
      foreach ($apis as $file) {
        $batch_set['operations'][] = array(__CLASS__.'::createApiFromJSON', array($apilm->getRunDir().'/'.$file));
      }
      foreach ($products as $file) {
        $batch_set['operations'][] = array(__CLASS__.'::createProductFromJSON', array($apilm->getRunDir().'/'.$file));
      }
      $batch_set['total'] = count($batch_set['operations']);
      $batch_set['count'] = $batch_set['total'];
      _batch_populate_queue($batch, $batch_next_set);

    } else {
      $context['message'] = t('Skipping import. No new changes');
      $context['results']['class'] = $apilm; // stash
    }
    $context['results']['class'] = $apilm; // stash
  }

  static function update_repository($entity, &$context) {
    $apilm = $context['results']['class'];
    if($apilm->getDiff()) {
      $context['message'] = t('Updating repository with the last run metadata..');
      $meta = json_decode($entity->internal_metadata->value, true);
      $meta['last_run'] = $apilm->getRunDir();
      $meta['last_run_checksum'] = $apilm->getChecksum();
      $entity->set('internal_metadata', json_encode($meta));
      $entity->save();
    } else {
      $context['message'] = t('Skipping metadata updates. No new changes to import..');
      $context['results']['class'] = $apilm; // stash
    }
  }

  static function finished($success, $results, $operations) {
    if($success) {
      $apilm = $results['class'];
      if($apilm->getDiff()) {
        $message = t('Synchronization succeessful. Synchronized @count_apis APIs and @count_stages stages', array('@count_apis' => $apilm->getStore['counts']['apis'], '@count_stages' => $apilm->getStore['counts']['stages']));
      } else {
        $message = t('Operation successful. No new changes to import');
      }
    } else {
      $message = t('Synchronization failed. Consult your logs or contact support for details..');
      \Drupal::messenger()->addMessage($message);
    }
  }

  static function createApiFromJSON($file, &$context) {
    $context['message'] = t('Importing API artefacts ..');
    $json = json_decode(file_get_contents($file));
    $props = array('rid' => $json->rid, 'type' => 'api');
    if($json->env) {
      $props['env'] = $json->env;
    }
    $matches = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties($props);
    $source_local = \Drupal::entityQuery('node')->condition('uuid', $json->source)->execute();
    $source_nid = reset($source_local);
    if(!$node = reset($matches)) {
      $node = Node::create(['type' => 'api']);
    }
    $node->set('rid', $json->rid);
    $node->set('title', $json->title);
    $node->set('description', $json->description);
    $node->set('env', $json->env);
    $node->set('spec', $json->spec);
    $node->set('api_type', $json->api_type);
    $node->set('source', $source_nid);
    $node->set('internal_metadata', json_encode($json->internal_metadata));
    //$node->set('is_api_product', 1); // This should be sourced from a gateway or global configuration
    $node->save();
  }

  static function createProductFromJSON($file, &$context) {
    $context['message'] = t('Importing Product artefacts ..');
    $json = json_decode(file_get_contents($file));
    $matches = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['rid' => $json->rid, 'type' => 'product']);
    $source_local = \Drupal::entityQuery('node')->condition('uuid', $json->source)->execute();
    $source_nid = reset($source_local);
    if(!$node = reset($matches)) {
      $node = Node::create(['type' => 'product']);
    }
    $node->set('rid', $json->rid);
    $node->set('title', $json->title);
    $node->set('remote', json_encode($json->remote));
    $node->set('source', $source_nid);
    $apis = array();
    foreach($json->apis as $api) {
      //$api = (object) $api; // force cast
      $lookup = \Drupal::entityQuery('node')->condition('type', 'api')->condition('rid', $api->rid);
      if($api->env) {
        $lookup->condition('env', $api->env);
      }
      $api_lookup = $lookup->addTag('debug')->execute();
      \Drupal::logger('L204')->notice('<pre>'. print_r($api, true) . '</pre>');
      $apis[] = reset($api_lookup);
    }
    // there's probably a better way to update multi-val fields
    foreach($apis as $key => $api) {
      if($key == 0) {
        $node->set('apis', $api);
      } else {
        $node->get('apis')->appendItem(['target_id' => $api]);
      }
    }
    \Drupal::logger('AWSGatewayClass')->notice('<pre>'. print_r($apis, true) . '</pre>');
    $node->save();
  }
}
