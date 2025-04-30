<?php
/**
 * Plugin Name: ERP Sync
 * Description: Sync WooCommerce data with ERP system
 * Version: 1.0
 * Author: Vako Lovecraft
 */

// Define plugin constants
define('ERP_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ERP_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include other pages
require_once ERP_SYNC_PLUGIN_DIR . 'includes/_callbacks.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/settings-page.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync-logic.php';

// Specify Hooks/Filters
register_activation_hook('erp-sync', 'add_erpsync_defaults_fn');
add_action('admin_init', 'erpsync_init_fn' );
add_action('admin_menu', 'erpsync_add_page_fn');

// add admin menu icon link
add_action('admin_menu', 'add_ERP_menu_to_admin_sidebar');

function add_ERP_menu_to_admin_sidebar() {
    add_menu_page(
      'ERP-Sync',                 // Page title (browser tab)
      'ERP Sync',                 // Menu title (displayed in sidebar)
      'manage_options',           // Capability required (admin-level access)
      'erp-sync',                 // Menu slug (URL parameter)
      'erpsync_page_fn',      // Callback function to render the page
      'dashicons-randomize',  // Icon (Dashicon class)
      30                         // Position (lower number = higher placement)
    );
}

// Add sub page to the Settings Menu
function erpsync_add_page_fn() {
	add_options_page(
    'ERP Sync', // page title displayed in browser title bar    
    'ERP Sync', // display link in the settings menu
    'administrator', // access level
    'erp-sync', // unique page name
    'erpsync_page_fn'); // callback function to display the options form
}

// Define default option settings
function add_erpsync_defaults_fn() {
	$tmp = get_option('plugin_erpsync'); // change for my options
    if(($tmp['chkbox1']=='on')||(!is_array($tmp))) {
		$arr = array(
      "dropdown1"=>"Orange",
      "text_area" => 
      "Space to put a lot of information here!",
      "text_string" => "Some sample text",
      "pass_string" => "123456",
      "chkbox1" => "", 
      "chkbox2" => "on", 
      "option_set1" => "Triangle");
		update_option('plugin_erpsync', $arr);
	}
}

// add manual sync button
add_action('admin_init', 'erpsync_handle_manual_sync');

// Handle the sync button submission
function erpsync_handle_manual_sync() {
  // Check if our form was submitted
  if (isset($_POST['erpsync_manual_sync'])) {
      // Verify the nonce for security
      if (!isset($_POST['erpsync_nonce']) || !wp_verify_nonce($_POST['erpsync_nonce'], 'erpsync_manual_sync')) {
          wp_die('Security check failed. Please try again.');
      }
      
      // Check user permissions
      if (!current_user_can('manage_options')) {
          wp_die('You do not have sufficient permissions to access this page.');
      }
      
      // Your custom sync code goes here
      $sync_result = perform_erp_sync();
      
      // Set a transient to show a message after redirect
      set_transient('erpsync_message', $sync_result ? 'Sync successful!' : 'Sync failed!', 60);
      
      // Redirect to the same page to prevent form resubmission
      wp_redirect(add_query_arg('page', 'erp-sync', admin_url('options-general.php')));
      exit;
  }
}

// Display the admin options page
function erpsync_page_fn() {
  ?>
    <div class="wrap">
      <div class="icon32" id="icon-options-general"><br></div>
      <h1>Integración Elconix ERP</h1>
      <!-- Some optional text here explaining the overall purpose of the options and what they relate to etc. -->
      <form action="options.php" method="post">
      <?php settings_fields('plugin_erpsync'); ?>
      <?php do_settings_sections('erp-sync'); ?>
      <p class="submit">
        <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
      </p>
      </form>
      <form action="" method="post">
        <?php wp_nonce_field('erpsync_manual_sync', 'erpsync_nonce'); ?>
        <p>
          <input type="submit" name="erpsync_manual_sync" id="erpsync-button" class="button" value="Sincronizar Ahora" />
        </p>
      </form>
    </div>
  <?php
  }


// ************************************************************************************************************

// Sync Now Button Handler

// Load the JS file only on your plugin's admin page
// add_action('admin_enqueue_scripts', 'erp_sync_enqueue_scripts');

// function erp_sync_enqueue_scripts($hook) {
//     // Only load on your plugin's admin page (replace 'erp-sync' with your page slug)
//     if ($hook != 'settings_page_erp-sync') {
//         return;
//     }

//     // Register and enqueue the script
//     wp_enqueue_script(
//         'erp-sync-ajax',                          // Handle
//         plugins_url('erp-sync-ajax.js', __FILE__), // Path to JS file
//         array('jquery'),                          // Dependency (jQuery)
//         '1.0',
//         true                                      // Load in footer
//     );

    // Pass PHP variables to JS (e.g., ajaxurl and nonce)
//     wp_localize_script(
//         'erp-sync-ajax',
//         'erp_sync_vars',
//         array(
//             'ajaxurl' => admin_url('admin-ajax.php'),
//             'nonce'   => wp_create_nonce('erp_sync_nonce')
//         )
//     );
// }

// Handle the AJAX request
// add_action('wp_ajax_erp_sync_action', 'erp_sync_callback');

// function erp_sync_callback() {
//     check_ajax_referer('erp_sync_nonce', 'security');
//     error_log('ERP Sync triggered!');
//     wp_send_json_success('Sync completed.');
// }


// // Sync Button
// add_action('wp_ajax_handle_sync_request', 'handle_sync_request_callback');
// // Add the Sync button to the plugin page
// function add_sync_button() {
/*   ?>
//   <div class="sync-button-container">
//       <button id="sync-button" class="button button-primary">Sync Now</button>
//   </div>
//   <script>
//       jQuery(document).ready(function($) {
//           $('#sync-button').on('click', function(e) {
//               e.preventDefault();
//               $.post(ajaxurl, {
//                   action: 'handle_sync_request',
//                   security: '<?php echo wp_create_nonce("sync-nonce"); ?>'
//               }, function(response) {
//                   console.log('Sync completed:', response);
//                   alert('Sync completed! Check debug.log for details.');
//               });
//           });
//       });
//   </script>
//   <?php
// }
// // Hook into the admin page (adjust 'your_plugin_page_slug' to match your plugin)
// add_action('admin_notices', 'add_sync_button'); // Or use a more targeted hook
*/

// function handle_sync_request_callback() {
//     // Verify nonce for security
//     check_ajax_referer('sync-nonce', 'security');

//     // Log to debug.log
//     error_log('Sync button clicked at ' . current_time('mysql'));

//     // Example: Simulate a sync task
//     error_log('Starting sync process...');
//     sleep(2); // Simulate work
//     error_log('Sync completed!');

//     wp_send_json_success('Sync initiated. Check debug.log.');
// }

// function sync_button_styles() {
//     echo '<style>
//         .sync-button-container {
//             margin: 20px 0;
//             padding: 10px;
//             background: #f9f9f9;
//             border: 1px solid #ddd;
//             display: inline-block;
//         }
//         #sync-button {
//             margin-right: 10px;
//         }
//     </style>';
// }
// add_action('admin_head', 'sync_button_styles');