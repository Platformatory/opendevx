<?php

namespace Drupal\odx_backoffice\Controller;

use Drupal\odx_backoffice\Controller\OdxBaseController;

/**
 * Returns responses for Platform Core routes.
 */
class OdxBackofficeController extends OdxBaseController {

  /**
   * Builds the response.
   */
  public function repositories() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('repository', 'Add an API Lifecycle Manager'),
    ];
    $group_id = $this->getContext($id=TRUE);
    $build['view'] = views_embed_view('repositories', 'admin', $group_id);
    return $build;
  }

  public function apis() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('api', 'Add an API'),
    ];
    $group_id = $this->getContext($id=TRUE);
    $build['view'] = views_embed_view('apis', 'admin', $group_id);
    return $build;
  }

  public function applications() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('application', 'Add an Application'),
    ];
    $group_id = $this->getContext($id=TRUE);
    $build['view'] = views_embed_view('applications', 'admin', $group_id);
    return $build;
  }

  public function users() {
    //TODO: users are different entity
    $build['view'] = views_embed_view('users', 'admin');
    return $build;
  }

  public function domains() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('domain', 'Add a Domain'),
    ];
    $build['view'] = views_embed_view('domains', 'admin');
    return $build;
  }

  public function members($group) {
    $add_url = '/backoffice' . $group->toUrl()->toString() . '/members/add';
    $build['add_link'] = [
      '#markup' => '<a href="'. $add_url . '">Add members</a>',
    ];
    $build['view'] = views_embed_view('group_members', 'block_1', $group->id());
    return $build;
  }

  public function addMember($group) {
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    $values = [
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
    ];
    $group_content = $this->entityTypeManager()->getStorage('group_content')->create($values);
    return \Drupal::service('entity.form_builder')->getForm($group_content, 'add');
  }

  public function editMember($group, $id) {
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');
    $group_content = $this->entityTypeManager()->getStorage('group_content')->load($id);
    return \Drupal::service('entity.form_builder')->getForm($group_content, 'edit');
  }

  public function deleteMember($group, $id) {
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');
    $group_content = $this->entityTypeManager()->getStorage('group_content')->load($id);
    return \Drupal::service('entity.form_builder')->getForm($group_content, 'edit');
  }

  public function products() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('product', 'Add a product'),
    ];
    $group_id = $this->getContext($id=TRUE);
    $build['view'] = views_embed_view('products', 'admin', $group_id);
    return $build;
  }

  public function content() {
    $group_id = $this->getContext($id=TRUE);
    $build['add_link'] = [
      '#theme' => 'content_add',
      '#usecase' => $this->buildAddLink('usecase', 'Add a Use Case', TRUE),
      '#blog' => $this->buildAddLink('news', 'Add a Blog', TRUE),
      '#event' => $this->buildAddLink('event', 'Add an Event', TRUE),
    ];
    $build['view'] = views_embed_view('domain_content', 'admin', $group_id);
    return $build;
  }

  public function operations() {
    $group_id = $this->getContext($id=TRUE);
    $build['view'] = views_embed_view('support', 'admin', $group_id);
    return $build;
  }


  public function billing_engines() {
    $group_id = $this->getContext($id=TRUE);
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('billing_engine', 'Add a Billing Engine'),
    ];

   $build['view'] = views_embed_view('billing_engines', 'admin');
   return $build;
  }

  public function plans() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('plan', 'Add a Billing plan'),
    ];
    $build['view'] = views_embed_view('billing_plans', 'admin');
    return $build;
  }

  public function subscriptions() {
    $build['add_link'] = [
      '#markup' => $this->buildAddLink('subscription', 'Add a Subscription'),
    ];
    $build['view'] = views_embed_view('subscriptions', 'admin');
    return $build;
  }


  public function integrations() {
    $odx_hub_form_class = 'Drupal\odx_integrations\Form\SettingsForm';
    $build['odx_hub_form'] = \Drupal::formBuilder()->getForm($odx_hub_form_class);

    $site_settings_form_class = 'Drupal\odx_backoffice\Form\SiteSettingsForm';
    $build['site_settings_form'] = \Drupal::formBuilder()->getForm($site_settings_form_class);

    $ga_reports_settings_form_class = 'Drupal\google_analytics_reports\Form\GoogleAnalyticsReportsAdminSettingsForm';
    $build['ga_reports_settings_form'] = \Drupal::formBuilder()->getForm($ga_reports_settings_form_class);
    return $build;
  }

  public function management_apis() {
    $openapi_generator = \Drupal::service('plugin.manager.openapi.generator')->createInstance('jsonapi');
    $openapi_ui = \Drupal::service('plugin.manager.openapi_ui.ui')->createInstance('swagger');
    $build = [
      '#type' => 'openapi_ui',
      '#openapi_ui_plugin' => $openapi_ui,
      '#openapi_schema' => $openapi_generator->getSpecification(),
    ];
    return $build;
  }

  public function workflows() {
    return $this->entityTypeManager->getListBuilder('workflow')->render();
  }

  public function branding() {

  }

  public function iam() {
    $build['content'] = [
      '#theme' => 'saml_settings',
      '#basic_settings' => \Drupal::formBuilder()->getForm('\Drupal\simplesamlphp_auth\Form\BasicSettingsForm'),
      '#local_auth' => \Drupal::formBuilder()->getForm('\Drupal\simplesamlphp_auth\Form\LocalSettingsForm'),
      '#user_info_and_sync' => \Drupal::formBuilder()->getForm('\Drupal\simplesamlphp_auth\Form\SyncingSettingsForm'),
    ];
    return $build;
  }

  public function rules() {
    $build['content'] = [
      '#theme' => 'business_rules',
      '#rules' => $this->entityTypeManager->getListBuilder('business_rule')->render(),
      '#actions' => $this->entityTypeManager->getListBuilder('business_rules_action')->render(),
      '#conditions' => $this->entityTypeManager->getListBuilder('business_rules_condition')->render(),
      '#variables' => $this->entityTypeManager->getListBuilder('business_rules_variable')->render(),
      '#schedule' => $this->entityTypeManager->getListBuilder('business_rules_schedule')->render(),
      '#settings' => \Drupal::formBuilder()->getForm('\Drupal\business_rules\Form\BusinessRulesSettingsForm'),
    ];
    return $build;
  }

  public function legal() {
    $build = views_embed_view('legal', 'admin');
    return $build;
  }

  public function discover() {
    $build['summary'] = views_embed_view('google_analytics_summary', 'block_2');
    $build['top_pages'] = views_embed_view('google_analytics_summary', 'attachment_top_pages');
    return $build;
  }

  public function guide() {
    $form_class = 'Drupal\checklistapi\Form\ChecklistapiChecklistForm';
    $build['form'] = \Drupal::formBuilder()->getForm($form_class, "backoffice_checklist");
    return $build;
  }

}
