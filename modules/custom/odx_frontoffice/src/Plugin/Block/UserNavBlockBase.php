<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Block\BlockBase;

abstract class UserNavBlockBase extends BlockBase {

  /**
   * The json parsing is same for both desktop and mobile menus.
   * Hence, hashing out into a class and using inheritence to DRY out user menu.
   */
  protected function getMenu() {
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('odx_frontoffice')->getPath();
    $jsonobj = file_get_contents($module_path . "/user_menu.json");
    return json_decode($jsonobj);
  }

}
