<?php

namespace Drupal\odx_frontoffice\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for ODX Frontoffice routes.
 */
class OdxFrontofficeAPIsController extends ControllerBase {

  /**
   * /product/{product}/api/{api}/try.
   */
  public function try($product, $api) {
    // TODO: check if $product has this $api, otherwise return 404.
    $apis = $product->get('apis')->referencedEntities();
    $first_api = reset($apis);
    $build['navigation'] = [
      '#theme' => 'product_navigation',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#api_count' => count($apis),
      '#try_url' => $product->toUrl()->toString() . $first_api->toUrl()->toString() . '/try',
      '#browse_url' => $product->toUrl()->toString() . $first_api->toUrl()->toString() . '/browse',
    ];

    $spec_url = '/api/' . $api->uuid() . '/spec';
    // attach swagger
    $build['swagger'] = [
      '#theme' => 'swagger',
    ];
    // TODO: view builder service customized for API
    // $api->spec->view();
    // load swagger
    $library_name = 'swagger_ui_formatter.swagger_ui_integration';
    $library_discovery = \Drupal::service('library.discovery');
    $swagger_ui_library_discovery = \Drupal::service('swagger_ui_formatter.swagger_ui_library_discovery');
    if ($library_discovery->getLibraryByName('swagger_ui_formatter', $library_name) !== FALSE) {
      $library_dir = $swagger_ui_library_discovery->libraryDirectory();
      // Set the oauth2-redirect.html file path for OAuth2 authentication.
      $oauth2_redirect_url = \Drupal::request()->getSchemeAndHttpHost() . '/' . $library_dir . '/dist/oauth2-redirect.html';
      $build['swagger']['#attached'] = [
                  'library' => [
                    'swagger_ui_formatter/' . $library_name,
                  ],
                  'drupalSettings' => [
                    'swaggerUIFormatter' => [
                      "api-field" => [
                        'oauth2RedirectUrl' => $oauth2_redirect_url,
                        'swaggerFile' => $spec_url,
                        'validator' => 'default',
                        'validatorUrl' => '',
                        'docExpansion' => "list",
                        'showTopBar' => false,
                        'sortTagsByName' => false,
                        'supportedSubmitMethods' => ["get","put","post","delete","options","head","patch"],
                      ],
                    ],
                  ],
                ];
    }
    return $build;
  }

  /**
   * /product/{product}/api/{api}/browse.
   */
  public function browse($product, $api) {
    $apis = $product->get('apis')->referencedEntities();
    $first_api = reset($apis);
    $build['navigation'] = [
      '#theme' => 'product_navigation',
      '#label' => $product->getTitle(),
      '#url' => $product->toUrl()->toString(),
      '#api_count' => count($apis),
      '#try_url' => $product->toUrl()->toString() . $first_api->toUrl()->toString() . '/try',
      '#browse_url' => $product->toUrl()->toString() . $first_api->toUrl()->toString() . '/browse',
    ];
    // TODO: check if $product has this $api, otherwise return 404.
    $spec = '/api/' . $api->uuid() . '/spec';
    $api_type = $api->get('api_type')->value;
    \Drupal::logger('content_entity_example')->notice($api_type);
    if ($api_type === 'rest') {
      \Drupal::logger('content_entity_example')->notice('redoc');
      $build['content'] = [
        '#theme' => 'redoc',
        '#url' => $spec,
      ];
      $build['content']['#attached']['library'][] = 'opendevx_theme/redoc';
    } elseif ($api_type === 'graphql') {
      \Drupal::logger('content_entity_example')->notice('voyager');
      $build['content'] = [
        '#theme' => 'voyager',
        '#url' => $spec,
      ];
      $build['content']['#attached']['library'][] = 'opendevx_theme/voyager';
    } else {
      \Drupal::logger('content_entity_example')->notice('async');
      $build['content'] = [
        '#theme' => 'async_api',
        '#url' => $spec,
      ];
      $build['content']['#attached']['library'][] = 'opendevx_theme/asyncapi';
    }

    return $build;
  }

  /**
   * Builds the spec as raw response.
   */
  public function spec($uuid) {
    $entity = \Drupal::service('entity_type.manager')->getStorage('node')
      ->loadByProperties(['uuid' => $uuid, 'type' => 'api']);
    $entity = reset($entity);
    if ($entity) {
      $spec = $entity->get('spec')->value;
      $api_type = $entity->get('api_type')->value;
      if ($api_type === 'rest') {
        // change spec to inject API keys
        $spec = json_decode($entity->get('spec')->value, true);
        $spec['security'] = [
          ['app_id' => []],
        ];
        $spec['components']['securitySchemes'] = [
          'app_id' => [
              'type' => 'apiKey',
              'description' => 'API key to authorize requests.',
              'name'=> 'X-API-Key',
              'in'=> 'header',
          ],
        ];    
        $updated_spec = json_encode($spec, true);
        $response = new Response($updated_spec, 200, array());
      } else {
        $response = new Response($spec, 200, array());
      }
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
    else {
      throw new NotFoundHttpException();
    }
  }
}
