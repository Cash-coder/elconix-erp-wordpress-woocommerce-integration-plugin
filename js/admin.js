// admin.js
(function($) {
  'use strict';
  
  $(document).ready(function() {
      // Set first accordion as open by default
      $('.erp-sync-accordion').first().addClass('open');
      $('.erp-sync-accordion').first().find('.erp-sync-accordion-content').show();
      
      // Handle accordion clicks
      $('.erp-sync-accordion-header').on('click', function() {
          const accordion = $(this).parent();
          
          // Toggle current accordion
          accordion.toggleClass('open');
          accordion.find('.erp-sync-accordion-content').slideToggle(200);
      });
      
      // Enable/disable sub-options based on parent checkbox state
      $('input[name="erp_inventory_sync_options[woo_to_erp][enabled]"]').on('change', function() {
          const isChecked = $(this).is(':checked');
          toggleSubOptions('woo_to_erp', isChecked);
      });
      
      $('input[name="erp_inventory_sync_options[erp_to_woo][enabled]"]').on('change', function() {
          const isChecked = $(this).is(':checked');
          toggleSubOptions('erp_to_woo', isChecked);
      });
      
      // Initialize sub-options state
      initializeSubOptionsState();
      
      // Handle test connection button
      $('#test-erp-connection').on('click', function() {
          const button = $(this);
          const resultSpan = $('#connection-test-result');
          
          // Get API credentials
          const apiKey = $('input[name="erp_inventory_sync_options[connection][api_key]"]').val();
          const apiEndpoint = $('input[name="erp_inventory_sync_options[connection][api_endpoint]"]').val();
          
          if (!apiKey || !apiEndpoint) {
              resultSpan.html('<span style="color: red;">Please enter API credentials first</span>');
              return;
          }
          
          // Show spinner
          button.prop('disabled', true);
          resultSpan.html('<span class="spinner is-active" style="float: none; margin: 0;"></span> Testing connection...');
          
          // AJAX request to test connection
          $.ajax({
              url: ajaxurl,
              type: 'POST',
              data: {
                  action: 'erp_inventory_test_connection',
                  api_key: apiKey,
                  api_endpoint: apiEndpoint,
                  nonce: erp_inventory_sync.nonce
              },
              success: function(response) {
                  if (response.success) {
                      resultSpan.html('<span style="color: green;">Connection successful!</span>');
                  } else {
                      resultSpan.html('<span style="color: red;">Connection failed: ' + response.data + '</span>');
                  }
              },
              error: function() {
                  resultSpan.html('<span style="color: red;">Connection test failed</span>');
              },
              complete: function() {
                  button.prop('disabled', false);
              }
          });
      });
  });
  
  // Helper function to toggle sub-options
  function toggleSubOptions(section, enabled) {
      const subOptions = $('input[name^="erp_inventory_sync_options[' + section + '][sync_"]').closest('tr');
      
      if (enabled) {
          subOptions.addClass('active');
          subOptions.find('input').prop('disabled', false);
      } else {
          subOptions.removeClass('active');
          subOptions.find('input').prop('disabled', true);
      }
  }
  
  // Initialize sub-options state based on current checkbox values
  function initializeSubOptionsState() {
      const wooToErpEnabled = $('input[name="erp_inventory_sync_options[woo_to_erp][enabled]"]').is(':checked');
      const erpToWooEnabled = $('input[name="erp_inventory_sync_options[erp_to_woo][enabled]"]').is(':checked');
      
      toggleSubOptions('woo_to_erp', wooToErpEnabled);
      toggleSubOptions('erp_to_woo', erpToWooEnabled);
  }
  
})(jQuery);