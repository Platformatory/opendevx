<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a contextual navigation block.
 *
 * @Block(
 *   id = "odx_frontoffice_contextual_nav",
 *   admin_label = @Translation("Contextual Navigation"),
 *   category = @Translation("Platformatory"),
 *   context_definitions = {
 *     "group" = @ContextDefinition(
 *       "entity:group",
 *       label = @Translation("Current Group"),
 *       required = TRUE,
 *     )
 *   }
 * )
 */
class ContextualNavBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    if (($group = $this->getContextValue('group')) && $group->id()) {
      if ($group->hasPermission('view group_membership content', $account)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // TODO: add groups cache context
    $group = $this->getContextValue('group');
    $color_info = $group->get('primary_color')->getValue();
    \Drupal::logger('my_module')->notice($color_info[0]['color']);
    $build['content'] = [
      '#theme' => 'contextual_navigation',
      '#primary_color' => $color_info[0]['color'],
    ];
    return $build;
  }

}
