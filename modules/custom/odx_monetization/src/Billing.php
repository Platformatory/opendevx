<?php

namespace Drupal\odx_monetization;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Billing service.
 */
class Billing {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Billing object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * Update all subscriptions
   * @todo will be implemented when saving of subscription billing
   * is implemented.
   */
  public function updateAllSubscriptionBillings() {
    // @DCG place your code here.
  }

  /**
   * Update billing for a single subscription
   */
  public function updateSubscriptionBilling(EntityInterface $subscription, $from, $to) {
    $gateway_plugin = $this->getGatewayPlugin($subscription);
    $app_metadata = $this->getAppMetadata($subscription);
    $usage_provider = $this->getUsageProvider($subscription);
    if($usage_provider == 'standard_collector') {
      $usage = $this->getUsageFromLogs($app_metadata, $from, $to);
    } else {
      $usage = $this->getUsageFromGateway($app_metadata, $from, $to);
    }
    return $this->computeBilling($usage, $subscription);
  }

  /**
   * Get all active subscriptions
   * @return array
   */
  protected function getActiveSubscriptions() {
    use Drupal\Core\Datetime\DrupalDateTime;
    use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
    
    $now = new DrupalDateTime('now');
    $query = \Drupal::service('entity_type.manager')->getStorage('node')->getQuery();
    $query->condition('type', 'subscription');
    //$query->condition('end_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<=');
    return $query->execute();
  }

  /**
   * Get gateway plugin for a given subscription.
   * @todo this should be a proper plugin manager derived in future.
   */
  protected function getGatewayPlugin(EntityInterface $subscription) {
    $application = $subscription->get('applications')->first()->get('entity')->getTarget()->getValue();
    $product = $application->get('products')->first()->get('entity')->getTarget()->getValue();
    $source = $product->get('source')->first()->get('entity')->getTarget()->getValue();
    return odx_apilm_manager_service_instance($source);
  }

  /**
   * Get app metadata
   */
  protected function getAppMetadata(EntityInterface $subscription) {
    $application = $subscription->get('applications')->first()->get('entity')->getTarget()->getValue();
    return _odx_apilm_app_metadata($application);
  }


  /**
   * Save usage
   * @todo save in a metrics table intexed by time.
   */
  protected function saveUsage(array $usage) {
    // @DCG place your code here.
  }

  /**
   * Save computed billing
   * @todo save computed billing in a table.
   */
  protected function saveBilling(array $billing_info) {
    // @DCG place your code here.
  }

  /**
   * Get usage from gateway, $from and $to are dates in Y-m-d fmt.
   * @return array of usage for the duration
   */
  protected function getUsageFromGateway(array $app_metadata, $from, $to) {
    $duration = array(
      'start' => $from,
      'end' => $to,
    );
    $usage_details = _odx_monetization_get_usage_for_app($app, $duration);
    return reset($usage_details);
  }


  /**
   * Get usage from logs, $from and $to are dates in Y-m-d fmt.
   * @return array of usage for the duration
   */
  protected function getUsageFromLogs(array $app_metadata, $from, $to) {
    $duration = array(
      'start' => $from,
      'end' => $to,
    );
    $usage_details = [];
    return $usage_details;
  }

  /**
   * compute billing
   * @return array of computed bill
   */
  protected function computeBilling(array $usage_details, $subscription) {
    $plan = $subscription->get('billing_plan')->first()->get('entity')->getTarget()->getValue();
    $usage = $usage_details['usage'];
    $pricing = json_decode($plan->pricing_rules->value, JSON_OBJECT_AS_ARRAY);
    $bill = array();
    $bill['total'] = '';
    $bill['li'] = array();
    $currency = ' ' . $plan->currency->value;
    foreach($pricing as $tier) {
      // Full tier needs to be billed
      if($usage >= $tier['from'] && $usage >= $tier['up_to']) {
        $tier_units = $tier['up_to'] - $tier['from'];
        $units_label = \Drupal::translation()->formatPlural($tier_units, '1 unit', '@count units');
        $bill['li'][] = array(
          'label' => $units_label . ' @ '. $tier['unit_price'],
          'unit_price' => $tier['unit_price'] . $currency,
          'units' => $tier_units,
          'charges' => $tier_units * $tier['unit_price'] . $currency,
        );
        $bill['total'] += $tier_units * $tier['unit_price'];
      }
      // Partial tier needs to be billed
      if($usage > $tier['from'] && $usage <= $tier['up_to']) {
        $tier_units = $usage - $tier['from'];
        $units_label = \Drupal::translation()->formatPlural($tier_units, '1 unit', '@count units');
        $bill['li'][] = array(
          'label' => $units_label . ' @ '. $tier['unit_price'],
          'unit_price' => $tier['unit_price'] . $currency,
          'units' => $tier_units,
          'charges' => $tier_units * $tier['unit_price'] . $currency,
        );
        $bill['total'] += $tier_units * $tier['unit_price'];
      }
      // Tier should not be billed
      if($usage < $tier['from']) {
      }
    }
    if ($bill['total']) {
      $bill['total'] = $bill['total'] . $currency;
    }
    return $bill;
  }

  /**
   * Get billing usage provider
   * @return string
   */
  protected function getUsageProvider(EntityInterface $subscription) {
    $billing_plan = $subscription->get('billing_plan')->get('entity')->getTarget()->getValue();
    return $billing_plan->get('usage_provider')->value;
  }

}
