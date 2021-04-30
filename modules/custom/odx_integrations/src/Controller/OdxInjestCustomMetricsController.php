<?php

namespace Drupal\odx_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for ODX Integrations routes.
 */
class OdxInjestCustomMetricsController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Consume payload and store in metrics table.
   */
  public function metrics($uuid) {
    // TODO: should we check if $uuid is of valid subscription
    $request = \Drupal::request();
    $payload = json_decode($request->getContent(), TRUE);
    $query = $this->connection->insert('api_metrics')->fields(['uuid', 'name', 'created', 'data']);
    foreach ($payload as $metric) {
      $record = [
        'uuid' => $uuid,
        'name' => $metric['name'],
        'created' => strtotime($metric['created']),
        'data' => $metric['data'],
      ];
      $query->values($record);
    }
    $query->execute();
    return new JsonResponse([], 201, ['Content-Type'=> 'application/json']);
  }
}
