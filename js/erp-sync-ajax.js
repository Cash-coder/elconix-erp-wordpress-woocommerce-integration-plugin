// jQuery(function($) {
//   $('#sync-button').on('click', function(e) {
//       e.preventDefault();
//       $.post(
//           erp_sync_vars.ajaxurl,  // From wp_localize_script()
//           {
//               action: 'erp_sync_action',
//               security: erp_sync_vars.nonce
//           },
//           function(response) {
//               alert(response.data);
//           }
//       );
//   });
// });