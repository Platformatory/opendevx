<?php

namespace Drupal\odx_frontoffice;

use Drupal\Core\Database\Connection;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;

/**
 * Converts parameters for upcasting database record IDs to full std objects.
 *
 * @DCG
 * To use this converter specify parameter type in a relevant route as follows:
 * @code
 * odx_frontoffice.domain_slug_parameter_converter:
 *   path: example/{record}
 *   defaults:
 *     _controller: '\Drupal\odx_frontoffice\Controller\OdxFrontofficeController::build'
 *   requirements:
 *     _access: 'TRUE'
 *   options:
 *     parameters:
 *       record:
 *        type: domain_slug
 * @endcode
 *
 * Note that for entities you can make use of existing parameter converter
 * provided by Drupal core.
 * @see \Drupal\Core\ParamConverter\EntityConverter
 */
class DomainSlugParamConverter implements ParamConverterInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new DomainSlugParamConverter.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The default database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $alias = \Drupal::service('path_alias.manager')->getPathByAlias('/domain/'. $value);
    if ($alias) {
      $params = Url::fromUri("internal:" . $alias)->getRouteParameters();
      $entity_type = key($params);
      return \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'domain_slug';
  }

}
