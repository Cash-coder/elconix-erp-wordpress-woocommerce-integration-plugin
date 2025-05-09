<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/check_license.php';

// Sync function
function perform_erp_sync() {
  error_log('------------running sync------------');
  
  $options = get_option('plugin_erpsync');

  // get license key
  $license_key = $options['license_key'];
  error_log('license key provided by the user is : ' . $license_key);

  // Check license validity, if wrong: error message + stop func + exit func
  if (!check_license($license_key)) {
    // $message = "Your comment has been submitted";
    // echo "<script type='text/javascript'>alert('$message');</script>";

    error_log('license key invalid, sync function stopped');
    return false;
  }

  // foreach ($options as $option) {error_log($option);}

  error_log(
    'Sync mode for Woo to ERP is ' 
    . $options['schedule_mode_wooToErp'] 
    . ' | auto sync time set at: '
    . $options['schedule_time_wooToErp']
  );

  error_log(
    'Sync mode for ERP to Woo is ' 
    . $options['schedule_mode_erpToWoo'] 
    . ' | auto sync time set at: '
    . $options['schedule_time_erpToWoo']
  );
  
  // if woo to ERP sync is enabled   ********************************
  if (isset($options['woo_to_ERP']) && $options['woo_to_ERP'] == 1) {
    error_log('woo to ERP enabled');

    // if orders sync is enabled
    if (isset($options['orders_sync'])) { // && $options['orders_sync'] == 1) {
      error_log('orders sync enabled');
    }
  } 

  // if ERP to woo sync is enabled   ********************************
  if (isset($options['erp_to_woo']) && $options['erp_to_woo'] == 1) {
    error_log('ERP to Woo enabled');
  }

  error_log('**************** Sync End ****************');

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
      include_once(WP_PLUGIN_DIR . '/ERP-Sync/includes/sync-logic.php');
  }
  
  // Call your sync function
  perform_erp_sync();
  
  // Optional: Log that the sync was executed
  error_log('ERP Sync executed via Action Scheduler at ' . date('Y-m-d H:i:s') . ' Source: ' . $source);
}

}
