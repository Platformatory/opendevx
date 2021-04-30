<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a side nav block.
 *
 * @Block(
 *   id = "odx_frontoffice_user_menu",
 *   admin_label = @Translation("User Menu Block"),
 *   category = @Translation("Platformatory")
 * )
 */
class UserMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // @DCG Evaluate the access condition here.
    $condition = TRUE;
    return AccessResult::allowedIf($condition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('odx_frontoffice')->getPath();
    $jsonobj = file_get_contents($module_path . "/user_menu.json");
    $menu = json_decode($jsonobj);

    $build['content'] = [
      '#theme' => 'side_nav',
      '#menu' => $menu,
    ];
    return $build;
  }

}
