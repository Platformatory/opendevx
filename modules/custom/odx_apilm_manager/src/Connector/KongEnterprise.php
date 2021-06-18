<?php

namespace Drupal\odx_apilm_manager\Connector;
/**
 * Plugin implementation of the api_lifecycle_provider.
 *
 * @ApiLifecycleProvider(
 *   id = "KongEnterprise",
 *   label = @Translation("KongEnterprise"),
 *   description = @Translation("Kong Enterprise.")
 * )
 */
class KongEnterprise {

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
    if($this->credentials['auth_type'] == 'apikey') {
      $this->client = new \GuzzleHttp\Client(['base_uri' => $this->credentials['admin_api_uri'], 'headers' => ['apikey' => $this->credentials['apikey']]]);
    }
    if($this->credentials['auth_type'] == 'basic') {
      $this->client = new \GuzzleHttp\Client(['base_uri' => $this->credentials['admin_api_uri'], 'auth' => [$this->credentials['username'], $this->credentials['password']]]);
    }
    return $this;
  }

  public function disconnect() {
    unset($this->client);
  }

  public function fetchSync($type = array(), $since = FALSE, $batch_size = 500, $position = 0) {
    $this->client ? $this->client : $this->connect();
    //$this->store['products'] = $this->fetchProducts($batch_size, $position);
    $this->store['apis'] = $this->fetchApis($batch_size, $position);
    $this->checksum = $this->compute_checksum();
    return $this;
  }

  public function generateOdxOutput() {
    $this->store['output'] = array();
    foreach($this->store['apis'] as $api_id => $api) {
      $this->store['output'][] = array(
        'rid' => $api->id,
        'title' => $api->name,
        'type' => 'api',
        'env' => FALSE,
        'spec' => FALSE,
        //'description' => $api->description,
        'api_type' => 'rest',
        'source' => $this->metadata['uuid'],
        'internal_metadata' => array(
          'revision' => '',
          //'parent' => $api_id,
          'created' => $api->created_at,
          'updated' => $api->updated_at,
        ),
        'remote' => $api,
      );

    }
    //\Drupal::logger('AWSGatewayClass')->notice('<pre>'. print_r($this->store['output'], true) . '</pre>');
    return $this;
  }


  public function fetch($type = FALSE, $since = FALSE) {}

  public function fetchApis($batch_size = 500, $position = 0) {
    $this->client ? $this->client : $this->connect();
    $response = json_decode($this->client->get('services')->getBody());
    $apis = array();
    foreach($response->data as $item) {
      $apis[$item->id] = $item;
    }
    return $apis;
  }

  public function fetchProducts($batch_size = 0, $position = 0) {
    // Kong has no API products. return NULL
    return NULL;
  }

  public function fetchSpec($id, $stage = 'prod') {
  }

  public function fetchApi($api_id, $env_name = NULL) {
    $this->client ? $this->client : $this->connect();
    // TODO: Fetch single proxy and return it here
    return $remote;
  }

  public function diffLastRun() {
    $this->diff = TRUE;
    if($this->checksum === $this->metadata['last_run_checksum']) {
      $this->diff = FALSE;
    }
    //\Drupal::logger('AWSGatewayClass')->notice('<pre>'. print_r($this, true) . '</pre>');
  }

  public function generateOdxArtefacts() {
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
    \Drupal::logger('K0ngClass359Creds')->notice('<pre>'. print_r($app, true) . '</pre>');

    // TODO: Must figure out what types of app auth the API expects through the apis in $app['product'] or $app['apis']
    // Currently, this assumes oAuth2

    // First, provision or update the consumer
    $provision = $this->client->put('consumers/'. $app['owner'], ['username' => $app['owner'], 'custom_id' => $app['owner']])->getBody();
    $consumer = json_decode($provision);
    // Next, provision oAuth keys - assuming the credential endpoint is /oauth;
    // TODO: Support redirect_uri

    $credential_req = $this->client->post('consumers/'. $consumer->username . '/oauth2', ['form_params' => ['name' => $app['name']]])->getBody();
    $credential = json_decode($credential_req);
    $creds = array(
      'type' => 'keypair',
      'credential_id' => $credential->id,
      'consumer_key' => $credential->client_id,
      'consumer_secret' => $credential->client_secret,
      'scopes' => NULL,
      'metadata' => array ('created' => $credential->created_at, 'expires' => $NULL),
    );
    //\Drupal::logger('AWSGatewayClass359Creds')->notice('<pre>'. print_r($creds, true) . '</pre>');
    //\Drupal::logger('AWSGatewayClass360Credential')->notice('<pre>'. print_r(get_class_methods($credential), true) . '</pre>');
    return $creds;
  }

  public function createProduct($product) {
    $this->client ? $this->client : $this->connect();
    // TODO:
  }

  public function getAppLogs($app) {}

  public function getUsage($app) {
    $this->client ? $this->client : $this->connect();
    // TODO: Finish this.
    $usage = array();
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
    $keys = array('apis', 'products');
    return crc32(json_encode(array_intersect_key($this->store, array_flip($keys))));
  }

  protected function compute_run_sumary() {
    // This is shitty. Write a countable interface out here.
    $apis = count($this->store['apis']);
    $stages = count(array_column($this->store['apis'], 'stages'));
    return ['apis' => $apis, 'stages' => $stages];
  }

}
