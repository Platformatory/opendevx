<?php

namespace Drupal\odx_frontoffice\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;


/**
 * Provides an user nav block.
 *
 * @Block(
 *   id = "odx_frontoffice_user_nav",
 *   admin_label = @Translation("User Nav"),
 *   category = @Translation("Platformatory")
 * )
 */
class UserNavBlock extends UserNavBlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user_id = \Drupal::currentUser()->id();
    $entities = \Drupal::entityTypeManager()->getStorage('avatars_preview')
      ->loadByProperties([
        'avatar_generator' => 'gravatar',
        'uid' => 1,
      ]);
    if ($entities) {
      $avatar_preview = reset($entities);
      $avatar = $avatar_preview->getAvatar();
      if ($avatar) {
        $user_image_url = file_create_url($avatar->getFileUri());
      }
    } else {
      $user_image_url = '';
    }
    $build['content'] = [
      '#theme' => 'user_nav',
      '#menu' => $this->getMenu(),
      '#user_image_url' => $user_image_url,
    ];
    return $build;
  }

}
