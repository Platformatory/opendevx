<?php

namespace Drupal\odx_backoffice;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;


/**
 * Provides a breadcrumb builder for articles.
 */
class OdxBackofficeBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    MenuActiveTrailInterface $menu_active_trail,
    MenuLinkManagerInterface $menu_link_manager
) {
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
  }
  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Is this a part of odx admin menu?
    // TODO: got to do better than that
    $trail_ids = $this->menuActiveTrail->getActiveTrailIds('odx-administration');
    $trail_ids = array_filter($trail_ids);
    if ($trail_ids) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    $breadcrumb->addCacheContexts(['route.menu_active_trails:odx-administration']);
    $breadcrumb->addCacheContexts(['url.path']);
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), 'odx_backoffice.dashboard'));

    $trail_ids = $this->menuActiveTrail->getActiveTrailIds('odx-administration');
    foreach (array_reverse($trail_ids) as $key => $value) {
      if ($value) {
        $breadcrumb->addLink(
          new Link(
            $this->menuLinkManager->createInstance($value)->getTitle(),
            $this->menuLinkManager->createInstance($value)->getUrlObject()
          )
        );
      }
    }
    return $breadcrumb;
  }
}
