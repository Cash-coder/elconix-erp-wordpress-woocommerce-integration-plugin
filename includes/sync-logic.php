<?php

// Sync function
function perform_erp_sync() {
  error_log('------------running sync------------');
  
  $options = get_option('plugin_erpsync');

  // foreach ($options as $option) {error_log($option);}
  error_log($options['schedule_mode']);
  error_log($options['schedule_time']);
  

  // if woo to ERP sync is enabled
  if (isset($options['woo_to_ERP']) && $options['woo_to_ERP'] == 1) {
    error_log('woo to ERP enabled');

    // if orders sync is enabled
    if (isset($options['orders_sync'])) { // && $options['orders_sync'] == 1) {
      error_log('orders sync enabled');
    }
  } 

  // sleep(5);
  return true;
}


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
