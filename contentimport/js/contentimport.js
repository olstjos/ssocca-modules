/**
 * @file
 * ISED CCH Content Import behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.contentimport = {
    attach: function (context, settings) {

      console.log('It works!');

    }
  };

} (jQuery, Drupal));

$ = jQuery;

function convertFiles() {
  $.ajax({
    url: '/en/admin/epic_import/convert_files',
    type: 'POST',
    success: function(data) {
    }
  });
  followLog();
}

function importContent() {
  $.ajax({
    url: '/en/admin/epic_import/content',
    type: 'POST',
    success: function(data) {
    }
  });
  followLog();
}

function importImages() {
  $.ajax({
    url: '/en/admin/epic_import/import_images',
    type: 'POST',
    success: function(data) {
    }
  });
  followLog();
}

function epicReset() {
  $.ajax({
    url: '/en/admin/epic_import/reset',
    type: 'POST',
    success: function(data) {
    }
  });
  followLog();
}

function replaceLinks() {
  $.ajax({
    url: '/en/admin/epic_import/replace_links',
    type: 'POST',
    success: function(data) {
    }
  });
  followLog();
}

function cleanUp() {
  $.ajax({
    url: '/en/admin/epic_import/clean',
    type: 'POST',
    success: function(data) {
    }
  });
  followLog();
}

function followLog() {
//  let logview = $('#contentimport-logview');
  let logview = document.getElementById('contentimport-logview');
  let from = 0;
  logview.innerHTML = '';
  let timer = setInterval(function() {
    $.ajax({
      url: '/en/admin/contentimport/get_log',
      type: 'GET',
      data: {
        from: from
      },
      success: function(data) {
        for (txt of data.messages) {
          logview.append(txt + "\n");
        }
        if (data.next === false) {
          clearInterval(timer);
          logview.append("---END---\n");
        }
        from = data.next;
        logview.scrollTop = logview.scrollHeight - logview.clientHeight;
      }
    });
  }, 1000);
}
