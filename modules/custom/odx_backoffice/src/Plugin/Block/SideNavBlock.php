<?php

namespace Drupal\odx_backoffice\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a side nav block.
 *
 * @Block(
 *   id = "odx_backoffice_side_nav",
 *   admin_label = @Translation("Side Nav"),
 *   category = @Translation("Platformatory")
 * )
 */
class SideNavBlock extends BlockBase {

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
    $module_path = $module_handler->getModule('odx_backoffice')->getPath();
    $jsonobj = file_get_contents($module_path . "/menu.json");
    $menu = json_decode($jsonobj);

    $build['content'] = [
      '#theme' => 'side_nav',
      '#menu' => $menu,
    ];
    return $build;
  }

}
