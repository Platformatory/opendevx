<?php

namespace Drupal\odx_integrations;

/**
 * Interface for api_lifecycle_provider plugins.
 */
interface ApiLifecycleProviderInterface {

  public function info();

  public function connect($credentials);

  // Async and sync commands
  public function fetchSync($type, $since);
  public function fetch($type, $since);

  // Typed objects: plurals and singulars
  public function fetchApis($since);
  public function fetchApps($since);
  public function fetchUsers($since);
  public function fetchUser($id);
  public function fetchApi($id);
  public function fetchApp($id);

 // Synchronous commands (apps)
  public function registerApp($app);
  public function getAppLogs($app);

// Arbitrary requests
  public function request($req);
}
