<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a domain hero 1 block.
 *
 * @Block(
 *   id = "odx_frontoffice_domain_hero_1",
 *   admin_label = @Translation("Domain Hero 1"),
 *   category = @Translation("ODX Frontoffice"),
 *   context_definitions = {
 *     "group" = @ContextDefinition(
 *       "entity:group",
 *       label = @Translation("Current Group"),
 *       required = TRUE,
 *     )
 *   }
 * )
 */
class DomainHero1Block extends BlockBase {

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
    //TODO: scrutinize permissions.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $group = $this->getContextValue('group');
    if ($group->hero_image->entity->field_media_image->entity) {
      $image_url = file_create_url($group->hero_image->entity->field_media_image->entity->getFileUri());
    } else {
      $image_url = 'https://images.unsplash.com/photo-1543892607-04657ef3a279?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80';
    }
    $build['content'] = [
      '#theme' => 'domain_hero_1',
      '#image_url' => $image_url,
      '#url' => $group->toUrl()->toString(),
    ];
    return $build;
  }

}
