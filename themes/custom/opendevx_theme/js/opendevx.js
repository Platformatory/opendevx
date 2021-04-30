/**
 * @file
 * opendevx behaviors.
 */

(function ($, Drupal) {

  'use strict';
  Drupal.behaviors.opendevx = {};
  var behavior = Drupal.behaviors.opendevx;

  behavior.attach = function (context, settings) {
    // highlight.js integration
    behavior.addFences();
    behavior.fencedCode();
    hljs.highlightAll();

    // voyager
    behavior.voyagerGraphQL();

    var $user_menu_toggle = $('#user-menu-toggle', context).once('opendevx');
    $user_menu_toggle.on('click', behavior.toggleUserMenu);

    // Active link in domain menu. We do this on client side
    // as this is not a proper Drupal menu yet
    $('#domain-navigation a').each(function () {
      if ($(this).attr('href') == window.location.pathname) {
        $(this).removeClass('border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700');
        $(this).addClass('border-indigo-500 text-gray-900');
      }
    });

    // Active link in product menu. We do this on client side
    // as this is not a proper Drupal menu yet
    $('#product-navigation a').each(function () {
      if ($(this).attr('href') == window.location.pathname) {
        $(this).removeClass('border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700');
        $(this).addClass('border-indigo-500 text-gray-900');
      }
    });
    // API pages
    $('a.api-link').each(function () {
      var full_url = window.location.pathname.split("/api");
      $(this).attr('href', full_url[0] + $(this).data('url'));
    });
    // toggle api try menu
    var $api_try_menu_toggle = $('div.api-try-toggle', context).once('opendevx');
    $api_try_menu_toggle.on('click', behavior.toggleApiTryMenu);

    // swagger options alter
    $(window).on('swaggerUIFormatterOptionsAlter', function( event, options) {
      console.log(options);
    });
  }

  /**
   * Handler to open user menu.
   */
  behavior.toggleUserMenu = function () {
    $('#user-menu').toggle('fast');
  }

  behavior.addFences = function() {
    var collection = [];
    $('pre').once('opendevx').each(function() {
        var nextCodeBlock = $(this).next().is('pre');
  
        collection.push($(this));
  
        if(!nextCodeBlock && collection.length > 1) {
            var container = $('<div class="fenced-tabs my-4"></div>');
            container.insertBefore(collection[0]);
            for(var i=0;i<collection.length;i++) {
                collection[i].appendTo(container);
            }
            collection = [];
        }
    });    
  }

  behavior.voyagerGraphQL = function() {
    $('div.graphql').once('opendevx').each(function(){
      var file_url = $(this).data('spec');
      $.getJSON(file_url, function (data) {
        GraphQLVoyager.init(document.getElementById('voyager'), {
          introspection: data
        });
      }).fail(function () {
        $('#voyager').html('Something went wrong. Please check logs or validate the schema.');
      });
    });
  }

  behavior.fencedCode = function() {
    $('div.fenced-tabs').once('opendevx').each(function () {
      var codes = $(this).find('pre');
      var count = 0;
      var id = behavior.makeId(6);
      var ul = $('<ul data-tabs></ul>')
      $.each(codes, function () {
        var code = $(this).find('code').get(0);
        if (!$(code).hasClass('hljs')) {
        var lang = code.classList[0].split('-')[1];
        var language = hljs.getLanguage(lang);
        var languageName = language.name;
        var code_id = id + "-" + lang;
        if (count == 0) {
          ul.append('<li><a data-tabby-default href="#' + code_id + '">' + languageName + '</a></li>');
        } else {
          ul.append('<li><a href="#' + code_id + '">' + languageName + '</a></li>');
        }
        $(code).wrap('<div id="' + code_id + '"></div>');
        hljs.highlightBlock(code);
      }
        count++;
      });
      $('div.fenced-tabs').prepend(ul);
      new Tabby('[data-tabs]');
    });
  }


  /**
   * Handler to open api try menu.
   */
  behavior.toggleApiTryMenu = function () {
    var nid = $(this).data('nid');
    $('#api-try-menu-' + nid).toggle('fast');
  }

  behavior.makeId = function (length) {
    var result = [];
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
      result.push(characters.charAt(Math.floor(Math.random() *
        charactersLength)));
    }
    return result.join('');
  }

} (jQuery, Drupal));
