<?php

namespace Drupal\odx_apilm_manager\Connector;

use Aws\ApiGateway;
use Aws\Exception\AwsException;
use Aws\Common\Exception\InvalidArgumentException;

/**
 * Plugin implementation of the api_lifecycle_provider.
 *
 * @ApiLifecycleProvider(
 *   id = "AwsApiGateway",
 *   label = @Translation("AwsApiGateway"),
 *   description = @Translation("AwsApiGateway description.")
 * )
 */
class AwsApiGateway {

  protected $credentials;
  protected $metadata;
  protected $client;
  protected $checksum;
  protected $store;
  protected $diff;
  protected $run_id;
  protected $run_dir;
  protected $output_dir;
  protected $manifest; // should be an import manifest for Drupal to easily figure out where to import things from

  public function getRunDir() {
    return $this->run_dir;
  }

  public function getChecksum() {
    return $this->checksum;
  }

  public function getDiff() {
    return $this->diff;
  }

  public function setRunDir($dir) {
    $this->run_dir = $run_dir;
  }

  public function setRunId($run_id) {
    $this->run_id = $id;
  }

  public function getStore($key = FALSE) {
    return $this->store;
  }

  public function setStore($store) {
    $this->store = $store;
  }

  public function __construct($credentials, $metadata) {
    $this->credentials = $credentials;
    $this->metadata = $metadata;
    $date = date("Y-m-d-H-i-s");
    $this->run_id = $date;
    $run_dir = $metadata['run_dir'].'/runs/'. $date;
    $this->run_dir = $run_dir;
  }

  public static function loadRun($metadata, $run_id) { // Factory to load a past run. Useful when processing asynchronously
    $instance = new self(NULL, $metadata);
    $instance->setRunId($run_id);
    $run_dir = $metadata['run_dir'].'/runs/'. $run_id;
    $store = json_decode(file_get_contents($run_dir.'/yield.json'), JSON_OBJECT_AS_ARRAY);
    $instance->setRunDir($run_dir);
    $instance->setStore($store);
    return $instance;
  }

  public function __sleep() {
    return ['credentials', 'metadata', 'store', 'run_dir', 'run_id', 'checksum', 'manifest'];
  }

  public function __wakeup() {
    $this->connect();
  }

  public function info() {
    return ["version" => 1, "sdk_version" => 1];
  }

  public function connect() {
    $this->client = new ApiGateway\ApiGatewayClient($this->credentials);
    return $this;
  }

  public function disconnect() {
    unset($this->client);
  }

  public function fetchSync($type = array(), $since = FALSE, $batch_size = 500, $position = 0) {
    $this->client ? $this->client : $this->connect();
    $this->store['products'] = $this->fetchProducts($batch_size, $position);
    $this->store['apis'] = $this->fetchApis($batch_size, $position);
    $this->store['counts'] = array('apis' => 0, 'stages' => 0, 'products' => count($this->store['products']));
    foreach($this->store['apis'] as $api_id => $api) {
      ++$this->store['counts']['apis'];
      $api_stages = $this->fetchStages($api_id);
      foreach($api_stages as $stage) { // possibly provide a filter for whitelisted stage names later on
        ++$this->store['counts']['stages'];
        $stageName = $stage['stageName'];
        $stage_details = array('metadata' => $stage, 'spec' => $this->fetchSpec($api_id, $stage['stageName']));
        $this->store['apis'][$api_id]['stages'][$stageName] = $stage_details;
      }
    }
    $this->checksum = $this->compute_checksum();
    return $this;
  }

  public function generateOdxOutput() {
    $this->store['output'] = array();
    foreach($this->store['apis'] as $api_id => $api) {
      foreach($api['stages'] as $stage_id => $stage) {
        $this->store['output'][] = array(
          'rid' => $api_id,
          'title' => $api['metadata']['name'] . ' -- ' . $stage_id,
          'type' => 'api',
          'env' => $stage_id,
          'spec' => $stage['spec'],
          'description' => $api['metadata']['description'],
          'api_type' => 'rest',
          'source' => $this->metadata['uuid'],
          'internal_metadata' => array(
            'deployment_id' => $stage['metadata']['deploymentId'],
            'parent' => $api_id,
            'created' => (string) $stage['metadata']['createdDate'],
            'updated' => (string) $stage['metadata']['lastUpdatedDate'],
          ),
          'remote' => $stage,
        );
      }
    }
    foreach($this->store['products'] as $product_id => $product) {
       $apis = array();
       foreach($product['apis'] as $api) {
         $apis[] = array('rid' => $api['id'], 'env' => $api['stage']);
       }

      $this->store['output'][] = array(
        'type' => 'product',
        'rid' => $product['id'],
        'title' => $product['name'],
        'quota' => $product['quota'],
        'remote' => $product['metadata'],
        'source' => $this->metadata['uuid'],
        'apis' => $apis,
      );

    }
    //\Drupal::logger('AWSGatewayClass')->notice('<pre>'. print_r($this->store['output'], true) . '</pre>');
    return $this;
  }

  public function fetch($type = FALSE, $since = FALSE) {}

  public function fetchApis($batch_size = 500, $position = 0) {
    $this->client ? $this->client : $this->connect();
    $results = $this->client->GetRestApis(['limit' => 500]); // un-hard code this limit and pass this as some kind of batch size
    $apis = array();
    foreach($results['items'] as $api) { // Handle this. This is just "item" if count = 1
      $api_id = $api['id'];
      $apis[$api_id]['metadata'] = $api;
    }
    return $apis;
  }

  public function fetchProducts($batch_size, $position) {
    $this->client ? $this->client : $this->connect();
    $results = $this->client->getUsagePlans(array('limit' => 500));
    $products = array();
    foreach($results['items'] as $product) {
      $product_id = $product['id'];
      $products[$product_id] = array(
        'name' => $product['name'],
        'description' => $product['description'],
        'id' => $product['id'],
        'quota' => $product['quota'],
        'metadata' => $product,
      );
      foreach($product['apiStages'] as $api) {
        $products[$product_id]['apis'][] = array(
          'id' => $api['apiId'], 'stage' => $api['stage']
        );
      }
    }
    return $products;
  }

  public function fetchStages($id) {
    $this->client ? $this->client : $this->connect();
    $stages = $this->client->getStages(['restApiId' => $id]);
    return $stages['item'];
  }

  public function fetchSpec($id, $stage = 'prod') {
    $this->client ? $this->client : $this->connect();
    $specs = $this->client->GetExport(['restApiId' => $id, 'exportType' => 'oas30', 'stageName' => $stage]);
    return $specs['body']->getContents();
  }

  public function fetchApps($since) {
    $apikeys = $this->client->getApiKeys(['includeValues' => FALSE, 'limit' => 500]);
  }


  public function fetchApi($api_id, $env_name = NULL) {
    $this->client ? $this->client : $this->connect();
    $api = $this->client->getRestApi(['restApiId' =>$api_id])->toArray();
    $stage = $this->client->getStage(['restApiId' => $api_id, 'stageName' => $env_name])->toArray();
    $spec = $this->fetchSpec($api_id, $env_name);
    $remote = array(
      'rid' => $api_id,
      'title' => $api['name'] . ' -- ' . $env_name,
      'type' => 'api',
      'env' => $stage['stageName'],
      'spec' => $spec,
      'description' => $api['description'],
      'api_type' => 'rest',
      'internal_metadata' => array(
        'deployment_id' => $stage['deploymentId'],
        'parent' => $api_id,
        'created' => (string) $stage['createdDate'],
        'updated' => (string) $stage['lastUpdatedDate'],
      ),
      'remote' => $stage,
    );
    return $remote;
  }

  public function diffLastRun() {
    $this->diff = TRUE;
    if($this->checksum === $this->metadata['last_run_checksum']) {
      $this->diff = FALSE;
    }
    //\Drupal::logger('AWSGatewayClass')->notice('<pre>'. print_r($this, true) . '</pre>');
  }

  public function save() {
    mkdir($this->run_dir, 0777, true);
    foreach($this->store['apis'] as $api_id => $api) {
      $this->write_to_file($this->run_dir.'/'. $api_id. '__api_metadata.json', json_encode($api['metadata'], JSON_PRETTY_PRINT)); // Write the spec
      foreach($api['stages'] as $stage_id => $stage) {
        $this->write_to_file($this->run_dir.'/'. $api_id.'-'. $stage_id .'__spec.json', $stage['spec']); // Write the spec
        $this->write_to_file($this->run_dir.'/'. $api_id.'-'. $stage_id .'__stage_metadata.json', json_encode($stage['metadata'], JSON_PRETTY_PRINT)); // Write the stage metadata
      }
    }
  }

  public function generateOdxArtefacts() {
    mkdir('public://specs', 0777, true);
    foreach($this->store['output'] as $artefact) {
       //$spec = $this->write_to_file('public://specs/'. $api['id']. '.spec.json', $api['spec']);
       //$api['spec'] = $spec;
       // TODO: Write a manifest or atleast a glob pattern type for files so they are easily recognizable
       $this->write_to_file($this->run_dir.'/'. $artefact['type'] . '.' . $artefact['rid'] .'.json', json_encode($artefact, JSON_PRETTY_PRINT));
    }
  }

  public function saveTree() {
    // TODO: Inject a store that saveTree saves to
    mkdir($this->run_dir, 0777, true);
    $this->write_to_file($this->run_dir.'/'. 'yield.json', json_encode($this->store, JSON_PRETTY_PRINT)); // TODO: JSON_UNESCAPED_UNICODE to save space
  }


  public function fetchUsers($since) {
    return FALSE;
  }

  public function fetchUser($id) {
    return FALSE;
  }

  public function fetchApp($id) {

  }

  public function createApp($app) {
    $this->client ? $this->client : $this->connect();
    $args = array(
      'customerId' => $app['owner'],
      'description' => $app['description'],
      'enabled' => TRUE,
      'stageKeys' => array(),
      'name' => $app['name'],
    );
    if(empty($app['product'])) { // If this is not beind done at a product level. Assumes that a bunch of APIs and stages are passed
      foreach($app['apis'] as $api) {
        $args['stageKeys'][] = array('restApiId' => $api['metadata']['parent'], 'stageName' => $api['env']);
      }
    }
    $result = $this->client->createApiKey($args)->toArray();
    $credentials = array(
      'type' => 'apikey',
      'credential_id' => $result['id'],
      'apikey' => $result['value'],
      'metadata' => array ('created' => (string) $result['createdDate'], 'updated' => (string) $result['lastUpdatedDate']),
    );

    if($app['product']) {
      $upk = $this->client->createUsagePlanKey([
        'keyId' => $credentials['credential_id'],
        'keyType' => 'API_KEY',
        'usagePlanId' => $app['product'],
      ]);
    }

    return $credentials;
  }

  public function createProduct($product) {
    $this->client ? $this->client : $this->connect();
    $args = array(
      'name' => $product['name'],
      'description' => $plan['description'],
      'quota' => array('limit' => $product['su_max'], 'offset' => 0, 'period' => $product['frequency']),
      'throttle' => array('burstLimit' => $product['config']['throttle']['burstLimit'], 'rateLimit' => $product['config']['throttle']['rateLimit']),
    );
    foreach($plan['apis'] as $product_id => $api) {
      $args['apiStages'][] = array('apiId' => $api['metadata']['parent'], 'stage' => $api['env'], 'throttle' => $plan['config']['throttle']);
    }
    $result = $this->client->createUsagePlan($args)->toArray();
    return array('rid' => $result->id, 'metadata' => $result);
  }

  public function getAppLogs($app) {}

  public function getUsage($app) {
    $this->client ? $this->client : $this->connect();
    $args = array(
      'keyId' => $app['id'],
      'usagePlanId' => $app['product'],
      'startDate' => $app['duration']['start'],
      'endDate' => $app['duration']['end'],
      'limit' => 500,
    );
    if (!$app['product']) {
      \Drupal::logger('my_module')->notice('No usage plan ID.');
      return [];
    }
    $result = $this->client->getUsage($args)->toArray();
    $app_id = $app['id'];
    $usage_li = $result['items'][$app_id];
    $last = $usage_li[array_key_last($usage_li)];
    $total_usage = array_sum(array_column($usage_li, 0));

    $period = new \DatePeriod(
      new \DateTime($args['startDate']),
      new \DateInterval('P1D'),
      (new \DateTime($args['endDate']))->modify('+1 day'), // needed because date range is off by one
    );

    $usage = array(
      'service_units' => 'api_calls',
      'usage' => $total_usage,
      'balance' => $last[1],
      'percentage_remaining' => 100 - ($total_usage / array_sum($last)) * 100,
    );

    foreach($period as $key => $value) {
      $usage['details'][$value->format('Y-m-d')] = $usage_li[$key];
    }
    return $usage;
  }

  public function request($req) {}

  protected function write_to_file($file, $contents, $type = FALSE) {
    //$this->manifest[$type] = $file; TODO: Write a manifest file with the checksum
    // TODO: Compress the file
    $write = file_put_contents($file, $contents);
    if($write) {
      return $file;
    }
  }

  protected function compute_checksum() {
    return crc32(json_encode($this->store['apis']));
  }

  protected function compute_run_sumary() {
    // This is shitty. Write a countable interface out here.
    $apis = count($this->store['apis']);
    $stages = count(array_column($this->store['apis'], 'stages'));
    return ['apis' => $apis, 'stages' => $stages];
  }

}
