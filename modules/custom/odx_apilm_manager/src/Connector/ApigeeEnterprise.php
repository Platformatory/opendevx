<?php

namespace Drupal\odx_apilm_manager\Connector;

use Apigee\Edge\Api\Management\Controller\DeveloperController;
use Apigee\Edge\Api\Management\Controller\ApiProductController;
use Apigee\Edge\Api\Management\Entity\Developer;
use Apigee\Edge\Api\Management\Controller\DeveloperAppController;
use Apigee\Edge\Api\Management\Controller\DeveloperAppCredentialController;
use Apigee\Edge\Api\Management\Entity\DeveloperApp;
use Apigee\Edge\Api\Management\Query\StatsQuery;
use Apigee\Edge\Exception\ApiException;
use Apigee\Edge\Exception\ClientErrorException;
use Apigee\Edge\Exception\ServerErrorException;
use Apigee\Edge\Client;
use Http\Message\Authentication\BasicAuth;
use League\Period\Period;

/**
 * Plugin implementation of the api_lifecycle_provider.
 *
 * @ApiLifecycleProvider(
 *   id = "ApigeeEnterprise",
 *   label = @Translation("ApigeeEnterprise"),
 *   description = @Translation("Apigee Enterprise.")
 * )
 */
class ApigeeEnterprise {

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
    $auth = new BasicAuth($this->credentials['username'], $this->credentials['password']);
    $this->client = new client($auth);
    return $this;
  }

  public function disconnect() {
    unset($this->client);
  }

  public function fetchSync($type = array(), $since = FALSE, $batch_size = 500, $position = 0) {
    $this->client ? $this->client : $this->connect();
    $this->store['products'] = $this->fetchProducts($batch_size, $position);
    $this->store['apis'] = $this->fetchApis($batch_size, $position);
    $this->checksum = $this->compute_checksum();
    return $this;
  }

  public function generateOdxOutput() {
    $this->store['output'] = array();
    foreach($this->store['apis'] as $api_id => $api) {
      $this->store['output'][] = array(
        'rid' => $api['name'],
        'title' => $api['name'],
        'type' => 'api',
        'env' => FALSE,
        'spec' => FALSE,
        //'description' => '$api['description']',
        'api_type' => 'rest',
        'source' => $this->metadata['uuid'],
        'internal_metadata' => array(
          'revision' => $api['revision'],
          //'parent' => $api_id,
          'created' => (string) $api['metaData']['createdAt'],
          'updated' => (string) $api['metaData']['lastModifiedAt'],
        ),
        'remote' => $api,
      );

    }
    foreach($this->store['products'] as $product_id => $product) {
      $apis = array();
      foreach($product['apis'] as $api) {
        $apis[] =  array('rid' => $api);
      }
      $this->store['output'][] = array(
        'type' => 'product',
        'rid' => $product['id'],
        'title' => $product['name'],
        'quota' => $product['quota'],
        'remote' => $product['remote'],
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
    $query_params = array('count' => $batch_size);
    $uri = $this->client->getUriFactory()->createUri("/organizations/". $this->credentials['organization'].'/apis');
    $results = json_decode($this->client->get($uri)->getBody()->getContents(), true); // un-hard code this limit and pass this as some kind of batch size
    //\Drupal::logger('ApigeEneterpriseClass')->notice('<pre>'. print_r(gettype($results), true) . '</pre>');
    $apis = array();
    foreach($results as $item) { // Handle this. This is just "item" if count = 1
      $uri = $this->client->getUriFactory()->createUri("/organizations/". $this->credentials['organization'].'/apis/'. $item);
      $api = json_decode($this->client->get($uri)->getBody()->getContents(), true); // un-hard code this limit and pass this as some kind of batch size
      $api['type'] = 'api';
      $apis[] = $api;
      //\Drupal::logger('ApigeEneterpriseClass')->notice('<pre>'. print_r($api, true) . '</pre>');
    }
    return $apis;
  }

  public function fetchProducts($batch_size = 0, $position = 0) {
    $this->client ? $this->client : $this->connect();
    $ac = new ApiProductController($this->credentials['organization'], $this->client);
    //\Drupal::logger('ApigeEneterpriseClass')->notice('<pre>'. print_r(get_class_methods($this->client), true) . '</pre>');
    $products = array();
    foreach ($ac->getEntities() as $product) {
      //\Drupal::logger('ApigeEneterpriseClass')->notice('<pre>'. print_r($product->getProxies(), true) . '</pre>');
      // Lazy load API products here with load.
      //$product = $ac->load($productName);
      //$products[] = $product->getDisplayName();
      $products[] = array(
        'name' => $product->getDisplayName(),
        'type' => 'product',
        'description' => $product->getDescription(),
        'remote' => $product,
        'env' => $product->getEnvironments(),
        'id' => $product->id(),
        'apis' => $product->getProxies(),
        'quota' => array('interval' => $product->getQuota(), 'interval' => $product->getQuotaInterval(), 'timeunit' => $product->getQuotaTimeUnit()),
        'attributes' => $product->getAttributes(),
      );
    }

    return $products;
  }

  public function fetchSpec($id, $stage = 'prod') {
    $this->client ? $this->client : $this->connect();
    $specs = $this->client->GetExport(['restApiId' => $id, 'exportType' => 'oas30', 'stageName' => $stage]);
    return $specs['body']->getContents();
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

    $dc = new DeveloperController($this->credentials['organization'], $this->client);

    try {
        $developer = $dc->load($app['owner']);
    } catch (ClientErrorException $e) {
        // HTTP code >= 400 and < 500. Ex.: 401 Unauthorised.
        if ($e->getEdgeErrorCode()) {
          \Drupal::logger('AWSGatewayClass302')->notice('<pre>'. print_r($e->getEdgeErrorCode(), true) . '</pre>');
          $developer = new Developer(['email' => $app['owner'], 'firstName' => $app['owner'], 'lastName' => $app['owner'], 'userName' => $app['owner']]);
          $dc->create($developer);
            //print $e->getEdgeErrorCode();
        } else {
        }
    } catch (ServerErrorException $e) {
        // HTTP code >= 500 and < 600. Ex.: 500 Server error.
    } catch (ApiException $e) {
        // Anything else, because this is the parent class of all the above.
    }

    $dac = new DeveloperAppController($this->credentials['organization'], $app['owner'], $this->client);
    $developerApp = new DeveloperApp(['name' => $app['name']]);
    $developerApp->setDisplayName($app['name']);
    //$developerApp->setAttributes(new AttributesProperty(['bar' => 'baz']));
    $dac->create($developerApp);

    $dacc = new DeveloperAppCredentialController($this->credentials['organization'], $app['owner'], $developerApp->id(), $this->client);
    $apiProducts = [$app['product']];
    \Drupal::logger('AWSGatewayClass322Creds')->notice('<pre>'. print_r($apiProducts, true) . '</pre>');
    //$scopes = ['scope 1', 'scope 2'];

    // Add products, attributes, and scopes to the auto-generated credential that was created along with the app.
    $credentials = $developerApp->getCredentials();
    $credential = reset($credentials);
    $dacc->addProducts($credential->id(), $apiProducts);
    //$dacc->updateAttributes($credential->id(), $credAttributes);
    //$dacc->overrideScopes($credential->id(), $scopes);

    // Create a new, auto-generated credential that expires after 1 week.
    //$dacc->generate($apiProducts, $developerApp->getAttributes(), $developerApp->getCallbackUrl(), $scopes, 604800000);

    // Create a credential with a specific key and secret and add the same products, attributes and scopes to it.
    //$credential = $dacc->create('MY_CONSUMER_KEY', 'MY_CONSUMER_SECRET');
    //$dacc->addProducts($credential->id(), $apiProducts);
    //$dacc->updateAttributes($credential->id(), $credAttributes);
    //$dacc->overrideScopes($credential->id(), $scopes);

    $creds = array(
      'type' => 'keypair',
      'credential_id' => $credential->id(),
      'consumer_key' => $credential->getConsumerKey(),
      'consumer_secret' => $credential->getConsumerSecret(),
      'scopes' => $credential->getScopes(),
      'metadata' => array ('created' => $credential->getIssuedAt(), 'expires' => $credential->getExpiresAt()),
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
    $sc = new \Apigee\Edge\Api\Management\Controller\StatsController($this->credentials['environment'], $this->credentials['organization'], $this->client);
    // Read more about Period library usage here: http://period.thephpleague.com/3.0
    $q = new StatsQuery(['message_count'], new Period('now - 7 days', 'now'));
    $q->setFilter("(developer_email eq '{$app['owner']}' and developer_app eq '{$app['name']}')");
    try {
        $result = $sc->getOptimizedMetricsByDimensions(['apps'], $q);
        //print_r($result);
    } catch (ClientErrorException $e) {
        // HTTP code >= 400 and < 500. Ex.: 401 Unauthorised.
        if ($e->getEdgeErrorCode()) {
            //echo $e->getEdgeErrorCode();
        } else {
            //echo $e;
        }
    } catch (ServerErrorException $e) {
        // HTTP code >= 500 and < 600. Ex.: 500 Server error.
    } catch (ApiRequestException $e) {
        // The request has failed, ex.: networking issues.
    } catch (ApiException $e) {
        // Anything else, because this is the parent class of all the above.
    }

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
