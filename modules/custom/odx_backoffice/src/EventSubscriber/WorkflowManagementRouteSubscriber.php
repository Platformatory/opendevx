<?php

namespace Drupal\odx_backoffice\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * ODX Backoffice route subscriber.
 */
class WorkflowManagementRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // TODO: show the backoffice version of the form only if evoked from backoffice
    if (true) {
      foreach ($collection->all() as $route_name => $route) {
        // Enable ODX theme for workflow/* routes, only if current route is backoffice.
        if ((strpos($route_name, 'entity.workflow') === 0)
          || (strpos($route_name, 'entity.business_rule') === 0)
          || (strpos($route_name, 'system.batch_page') === 0)
          || (strpos($route_name, 'business_rules') === 0)) {
          $route->setOption('_odx', TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
