jQuery(document).ready(function($) {
  $('#erp-sync-now').on('click', function(e) {
      e.preventDefault();
      var $button = $(this);
      var $spinner = $('.spinner');
      var $results = $('#sync-results');
      
      $button.prop('disabled', true);
      $spinner.addClass('is-active');
      $results.html('<p>Syncing...</p>');
      
      $.ajax({
          url: woo_erp_vars.ajaxurl,
          type: 'POST',
          data: {
              action: 'woo_erp_manual_sync',
              nonce: woo_erp_vars.nonce
          },
          success: function(response) {
              var html = '<div class="notice notice-success"><p>Sync completed successfully</p>';
              if (response.data) {
                  html += '<ul>';
                  $.each(response.data, function(key, result) {
                      html += '<li>' + result.message + '</li>';
                  });
                  html += '</ul>';
              }
              html += '</div>';
              $results.html(html);
          },
          error: function(xhr) {
              $results.html('<div class="notice notice-error"><p>Error: ' + (xhr.responseJSON?.data || 'Sync failed') + '</p></div>');
          },
          complete: function() {
              $button.prop('disabled', false);
              $spinner.removeClass('is-active');
          }
      });
  });
});