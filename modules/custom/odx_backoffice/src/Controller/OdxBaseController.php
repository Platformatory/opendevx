<?php

namespace Drupal\odx_backoffice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Returns responses for ODX Backoffice routes.
 */
class OdxBaseController extends ControllerBase {

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
    );
  }

/**
 * if $id is TRUE, returns just the context ID
 */
  protected function getContext($id=FALSE) {
    $context_id = \Drupal::request()->query->get('context');
    if ($context_id) {
      if ($id == TRUE) {
        return $context_id;
      }
      $context = $this->entityTypeManager->getStorage('group')->load($context_id);
      return $context;
    }
  }

  protected function buildAddLink($entity_type, $caption, $just_link=FALSE) {
    $route_name = \Drupal::routeMatch()->getRouteName();
    $param_url = Url::fromRoute($route_name);
    $query_params = [
      'destination' => $param_url->toString(),
    ];
    $context = $this->getContext(TRUE);
    if ($context) {
      $query_params['context'] = $context;
    }
    $url = Url::fromRoute('odx_forms.add_odx_entity_show_form', ['entity_type' => $entity_type]);
    $url->setOption('query', $query_params);
    $url->setOption('attributes', [
      'class' => [
        'btn-floating',
        'btn-large',
        'waves-effect',
        'waves-light',
      ],
    ]);
    $link = $url->toString();
    if ($just_link) {
      return $link;
    }
    return "<a href='$link' class='btn-floating btn-large waves-effect waves-light'><i class='material-icons'>add</i></a>";
  }
}
