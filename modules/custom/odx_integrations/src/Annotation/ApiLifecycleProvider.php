<?php

namespace Drupal\odx_integrations\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines api_lifecycle_provider annotation object.
 *
 * @Annotation
 */
class ApiLifecycleProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
