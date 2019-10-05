/* executed for
 /wp-admin/options-general.php
*/
(function($) {

  var switchTab = function (hash) {
    if (!hash) {
      return false;
    }

    var anchor = $('.nav-tab-wrapper a[href="' + hash + '"]');
    if (!anchor.length) {
      return false;
    }

    anchor.parent().children().removeClass('nav-tab-active');
    anchor.addClass('nav-tab-active');

    var form = $('#qtranxs-configuration-form');
    var tabId = hash.replace('#', '#tab-');
    var tabContents = $('.tabs-content');
    tabContents.children().addClass('hidden');
    tabContents.find('div' + tabId).removeClass('hidden');
    var action = form.attr('action').replace(/(#.*|$)/, hash);
    form.attr('action', action);

    try {
      sessionStorage.setItem('qtrans_admin_section', hash)
    }
    catch (e) {
      // no big deal if this can't be stored
      console.log('Failed to store qtrans_admin_section with sessionStorage', e);
    }

    return true;
  };

  var onHashChange = function (defaultHash) {
    var hash = window.location.hash;
    if (switchTab(hash)) {
      return;
    }

    hash = sessionStorage.getItem('qtrans_admin_section');
    if (switchTab(hash)) {
      return;
    }

    switchTab(defaultHash);
  };

  $(function() {
    $(window).bind('hashchange', function () {
      onHashChange();
    });
    onHashChange('#general');

    $('#qtranxs_debug_query').on('click', function () {
      var ca = document.cookie.split(';');
      var clientInfo = {
        'cookies': [],
        'navigator': navigator.userAgent
      };
      for (var i = 0; i < ca.length; i++) {
        var cookieStr = ca[i].trim();
          if (cookieStr.indexOf('qtrans') === 0) {
            clientInfo['cookies'].push(cookieStr);
          }
      }

      $('#qtranxs_debug_info').show();
      $('#qtranxs_debug_info_client').val(JSON.stringify(clientInfo, null, 2));
      $('#qtranxs_debug_info_server').val('...');

      $.ajax({
          url: ajaxurl,
          dataType : 'json',
          data : {
            action: 'admin_debug_info'
          },
          success: function(response) {
            console.log('debug-info', response);
            $('#qtranxs_debug_info_server').val(JSON.stringify(response, null, 2));
          },
          error: function(xhr) {
            console.error('debug-info', xhr);
            $('#qtranxs_debug_info_server').val('An error occurred: status=' + xhr.status + ' (' + xhr.statusText + ')');
          }
      });
    })
  });
})(jQuery);
