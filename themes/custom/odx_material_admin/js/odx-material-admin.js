/**
 * @file
 * ODX Material Admin behaviors.
 */

(function ($, Drupal) {

  'use strict';
  Drupal.behaviors.odxMaterialAdmin = {};
  var behavior = Drupal.behaviors.odxMaterialAdmin;

  behavior.attach = function (context, settings) {
    $('.dropdown-trigger').dropdown({
      "coverTrigger": false,
      "onOpenStart": function() {
        // remove query params from global context
        $('#context-dropdown .global-context').attr('href', window.location.pathname);
      },
    });
    var $sidenav_trigger = $('.sidenav-trigger', context).once('odx-material-admin');
    $sidenav_trigger.on('click', behavior.openSideNav);
    var $toggle_extra_info_view = $('span.toggle-extra-info', context).once('odx-material-admin');
    $toggle_extra_info_view.on('click', behavior.toggleExtraInfo);
    // Active link in menu. We do this on client side
    // as this is not a proper Drupal menu
    $('ul.sidenav-menu li a').each(function () {
      if ($(this).attr('href') == window.location.pathname) $(this).addClass('active');
    });
    // SAML page tabs behaviour
    $('#saml-settings').tabs();

    // business rules page tabs behaviour
    $('#business-rules').tabs();

    // Add button dropdown trigger
    $('#add-content-dropdown').dropdown({"coverTrigger": false});
  }

  /**
   * Handler to open the navigation.
   */
  behavior.openSideNav = function () {
    $('#sidenav-left').toggle('fast');
  }

  /**
   * Handler to open the extra info row in a table view.
   */
  behavior.toggleExtraInfo = function () {
    var uuid = $(this).data('content');
    $('#'+uuid).toggle();
  }
} (jQuery, Drupal));
