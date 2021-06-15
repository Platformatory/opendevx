<?php

namespace Drupal\odx_frontoffice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a ODX Frontoffice form.
 */
class AppCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odx_frontoffice_app_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $plan_uuid = \Drupal::request()->query->get('plan');

    if ($plan_uuid) {
      $plan = \Drupal::service('entity_type.manager')->getStorage('node')
            ->loadByProperties(['uuid' => $plan_uuid, 'type' => 'plan']);
      $plan = reset($plan);
    }


    $product_uuid = \Drupal::request()->query->get('product');

    $product = \Drupal::service('entity_type.manager')->getStorage('node')
          ->loadByProperties(['uuid' => $product_uuid, 'type' => 'product']);
    $product = reset($product);
    $app_id = $this->generateRandomString();
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#value' => 'app-'. $app_id . '-for-product-'. $this->slugify($product->label()) . '-subscription-'. $this->slugify($plan->label()),
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $form['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#required' => FALSE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#required' => FALSE,
    ];

    $form['product'] = [
      '#type' => 'hidden',
      '#title' => t('Product'),
      '#value' => $product->id(),
    ];

    $plans = $this->getPlans($product);

    if ($plan) {
      $form['billing_plan'] = [
        '#type' => 'radios',
        '#title' => t('Billing plan'),
        '#options' => $plans,
        '#default_value' => $plan->id(),
        '#description' => t('Choose an appropriate billing plan for your app.'),
      ];
  } else {
      // show billing plans if the product is
      // associated with one or more of them
      if ($plans) {
        $form['billing_plan'] = [
          '#type' => 'radios',
          '#title' => t('Billing plan'),
          '#options' => $plans,
          '#default_value' => $plan->id(),
          '#description' => t('Choose an appropriate billing plan for your app.'),
        ];
      }
    }

    $subscription_period_options = [
      'monthly' => t('Monthly'),
      'weekly' => t('Weekly'),
    ];
    $form['subscription_period'] = [
      '#type' => 'radios',
      '#title' => t('Subscription period'),
      '#options' => $subscription_period_options,
    ];
    $form['auto_renew'] = [
      '#type' => 'checkbox',
      '#title' => t('Auto Renew'),
      '#default_value' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    $form['#theme'] = 'app_create_form';
    return $form;
  }

  protected function slugify($string) {
    // Replace with dashes anything that isn't A-Z, numbers, dashes, or underscores.
    return strtolower(preg_replace('/[^a-zA-Z0-9-]+/', '-', $string));
  }  

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // if (mb_strlen($form_state->getValue('message')) < 10) {
    //   $form_state->setErrorByName('name', $this->t('Message should be at least 10 characters.'));
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // create app
    $currentAccount = \Drupal::currentUser();
    $currentAccount->id();
    $app = \Drupal::entityTypeManager()->getStorage('node')->create(['type' => 'application', 'title' => $form_state->getValue('name')]);
    $app->set('version', $form_state->getValue('version'));
    $app->set('description', $form_state->getValue('description'));
    $app->products[] = ['target_id' => (int) $form_state->getValue('product')];
    $app->set('uid', $currentAccount->id());
    $app->save();
    $this->messenger()->addStatus($this->t('Your app has been created.'));
    if ($form_state->getValue('billing_plan')) {
      $subscription = \Drupal::entityTypeManager()->getStorage('node')->create(['type' => 'subscription']);
      $subscription->set('title', 'Subscription for ' . $app->label());
      $subscription->set('uid', $currentAccount->id());
      $subscription->applications[] = ['target_id' => (int) $app->id()];
      $subscription->set('auto_renew', $form_state->getValue('auto_renew'));
      $subscription->billing_plan[] = ['target_id' => (int) $form_state->getValue('billing_plan')];
      // start date and end date
      $start_date = new \DateTime('now');
      $subscription->set('start_date', $start_date->format("Y-m-d\TH:i:s"));
      $start_date->modify('+1 month'); 
      $subscription->set('end_date', $start_date->format("Y-m-d\TH:i:s"));
      $subscription->save();
    }
    $this->messenger()->addStatus($this->t('You are subscribed to a billing plan.'));
  }

  protected function getProducts() {
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'product');
    $nodes = $query->execute();

    foreach($nodes as $nid) {
      $node = \Drupal::service('entity_type.manager')->getStorage('node')
          ->load($nid);
      $options[$nid] = $node->label();
    }
    return $options;
  }

  protected function getPlans(\Drupal\node\NodeInterface $product) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'plan')
      ->condition('products', $product->id());
    $nids = $query->execute();
    $plan_nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    $plans = [];
    foreach ($plan_nodes as $plan) {
      $plans[$plan->id()] = $plan->label();
    }
    return $plans;
  }

  protected function generateRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }  
}
