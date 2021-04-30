<?php

namespace Drupal\odx_integrations;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for api_lifecycle_provider plugins.
 * Do all the baseline/common implementation stuff here.
 */
abstract class ApiLifecycleProviderPluginBase extends PluginBase implements ApiLifecycleProviderInterface {


  // TODO: inject guzzle, entity manager, file related service, config related service.
  /**
   * {@inheritdoc}
   */
  public function info() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
