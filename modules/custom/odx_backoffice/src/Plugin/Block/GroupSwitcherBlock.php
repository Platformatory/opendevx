<?php

namespace Drupal\odx_backoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\Core\Cache\Cache;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a groupswitcher block.
 *
 * @Block(
 *   id = "odx_backoffice_group_switcher",
 *   admin_label = @Translation("GroupSwitcher"),
 *   category = @Translation("Custom")
 * )
 */
class GroupSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $group_membership_loader;

  /**
   * Constructs a new GroupSwitcherBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, AccountInterface $current_user, GroupMembershipLoaderInterface $group_membership_loader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->group_membership_loader = $group_membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('group.membership_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.query_args:context']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache_contexts = [
      'url.query_args:context',
    ];
    $current_context = '';
    $grps = $this->group_membership_loader->loadByUser($this->currentUser->getAccount());
    $context_id = \Drupal::request()->query->get('context');
    // TODO: if the context is something user has no access to, then return 404
    // maybe this should be in a route subscriber
    if ($context_id) {
      $context = \Drupal::service('entity_type.manager')->getStorage('group')->load($context_id);
    } else {
      $context = NULL;
    }
    foreach ($grps as $grp) {
      if ($context && ($context->id() == $grp->getGroup()->id())) {
        $current_context = $grp->getGroup()->label();
      } else {
        $contexts[$grp->getGroup()->id()] = $grp->getGroup()->label();
      }
    }
    $build['content'] = [
      '#theme' => 'odx_material_admin_context_switch',
      '#current_context' => $current_context,
      '#contexts' => $contexts,
    ];
    return $build;
  }

}
