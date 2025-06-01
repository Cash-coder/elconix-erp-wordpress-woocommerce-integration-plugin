<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/check_license.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

// Sync function
function perform_erp_sync() {
  logger('------------ Sync Start ------------');

  echo '
    <script>
    jQuery(".wrap").prepend(\'<div class="notice notice-info"><p>🔄 Sync in progress</p></div>\');
    </script>
    <div class="notice notice-info"><p>🔄 Sync in progress</p></div>
    ';  

    if (ob_get_level() > 0) {
      ob_flush();
      flush();
    }
  
  $options = get_option('plugin_erpsync');

  // get license key
  $license_key = $options['license_key'];
  logger('license key provided by the user is : ' . $license_key);

  // Check license validity, if wrong: error message + stop func + exit func
  if (!License::check_license($license_key)) {
    logger('license key invalid, sync function stopped');
    UserNotice::admin_notice_message('error', 'Clave de licencia inválida');
    return false;
  }

  // foreach ($options as $option) {logger($option);}

  logger(
    'Sync mode for Woo to ERP is ' 
    . $options['schedule_mode_wooToErp'] 
    . ' | auto sync time set at: '
    . $options['schedule_time_wooToErp']
  );

  logger(
    'Sync mode for ERP to Woo is ' 
    . $options['schedule_mode_erpToWoo'] 
    . ' | auto sync time set at: '
    . $options['schedule_time_erpToWoo']
  );
  
  // if woo to ERP sync is enabled   ********************************
  if (isset($options['woo_to_ERP']) && $options['woo_to_ERP'] == 1) {
    logger('woo to ERP enabled');

    // if orders sync is enabled
    if (isset($options['orders_sync'])) { // && $options['orders_sync'] == 1) {
      logger('orders sync enabled');
    }
  } 

  // if ERP to woo sync is enabled   ********************************
  if (isset($options['erp_to_woo']) && $options['erp_to_woo'] == 1) {
    logger('ERP to Woo enabled');

    // if prod sync is enabled
    if (isset($options['prods_sync'])) { // && $options['orders_sync'] == 1) {
      logger('orders sync enabled');
     
      $response = ERPtoWoo::sync_test($options);
      // if error
      if (!$response) {
        return false;
      }
    }

  }

  UserNotice::admin_notice_message('success', 'Sincronización completada con éxio');
  
  logger('**************** Sync End ****************');

  return true;



/**
 * Simple Action Scheduler Implementation
 * 
 * First, make sure you have Action Scheduler library included in your plugin:
 * - If you have WooCommerce, it's already available
 * - Otherwise, include it as a dependency via Composer or download directly
 */

// 1. Schedule a task to run after 10 seconds
function schedule_my_erp_sync() {
  // Make sure Action Scheduler is loaded
  if (!function_exists('as_schedule_single_action')) {
      return false;
  }
  
  // Schedule the sync function to run after 10 seconds
  $timestamp = time() + 10; // 10 seconds from now
  
  // The hook name that will trigger your function
  $hook = 'perform_erp_sync_hook';
  
  // Any arguments you want to pass to your function (optional)
  $args = array('source' => 'manual_trigger');
  
  // Schedule the action
  $action_id = as_schedule_single_action($timestamp, $hook, $args);
  
  return $action_id; // Return the action ID for potential unscheduling
}

// 2. Function to unschedule a specific action
function unschedule_my_erp_sync($action_id = null) {
  // If action ID is provided, unschedule that specific action
  if ($action_id) {
      return as_unschedule_action('perform_erp_sync_hook', null, null, array(), $action_id);
  }
  
  // Otherwise, unschedule all instances of this hook
  return as_unschedule_all_actions('perform_erp_sync_hook');
}

// 3. Hook up your function to the Action Scheduler hook
add_action('perform_erp_sync_hook', 'execute_erp_sync_via_action_scheduler', 10, 1);
function execute_erp_sync_via_action_scheduler($source = '') {
  // Include your sync logic file if needed
  if (!function_exists('perform_erp_sync')) {
      include_once(WP_PLUGIN_DIR . '/ERP-Sync/includes/sync/sync-logic.php');
  }
  
  // Call your sync function
  perform_erp_sync();
  
  // Optional: Log that the sync was executed
  logger('ERP Sync executed via Action Scheduler at ' . date('Y-m-d H:i:s') . ' Source: ' . $source);
}

}

// Utility function for logging
function logger($message) {
  UserNotice::log_message( '[sync-logic] ' . $message);
}



// add_action('admin_notices', function() {  
// if ($notice = get_transient('erp_sync_notice')) {
//     echo '<div class="notice notice-'.esc_attr($notice['type']).' is-dismissible">
//         <p>'.esc_html($notice['message']).'</p>
//     </div>';
//     delete_transient('erp_sync_notice');
// }
// });


