<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

/** Checks if sync_mode has changed after "save settings" or "Guardar Cambios" click by the user, mode:manual|auto and/or time_interval:xseconds, runs erpsync_schedule_action() to un/schedule actions on that basis
 * Compares old and new values from the schedule option, to see if something changed and action un/scheduling is needed
 * This function will run AFTER click on "Guardar Cambios" button, but BEFORE the page redirects back to the settings page
 * @param $options_old_values str:auto|manual before clicking save button
 * @param $options_new_values str:auto|manual after clicking save button
 * @return void
 */

class ERPsync_Action_Scheduler {
  public static function erpsync_scheduler_handler($options_old_values, $options_new_values) {
  
    // foreach ($old_value as $value) {
    //   logger($value);
    // }
    // foreach ($new_value as $value) {
    //   logger($value);
    // }
  
    // wooToErp sync mode and time interval (with fallbacks)
    $wooToErp_sync_mode_old_value = isset($options_old_values['schedule_mode_wooToErp']) ? $options_old_values['schedule_mode_wooToErp'] : 'manual';
    $wooToErp_sync_mode_new_value = isset($options_new_values['schedule_mode_wooToErp']) ? $options_new_values['schedule_mode_wooToErp'] : 'manual';
    $sync_time_interval_woo_to_erp_old_value = isset($options_old_values['sync_time_interval_woo_to_erp']) ? $options_old_values['sync_time_interval_woo_to_erp'] : '150';
    $sync_time_interval_woo_to_erp_new_value = isset($options_new_values['sync_time_interval_woo_to_erp']) ? $options_new_values['sync_time_interval_woo_to_erp'] : '150';

    // erpToWoo sync mode and time interval (with fallbacks) 
    $erpToWoo_sync_mode_old_value = isset($options_old_values['schedule_mode_erpToWoo']) ? $options_old_values['schedule_mode_erpToWoo'] : 'manual';
    $erpToWoo_sync_mode_new_value = isset($options_new_values['schedule_mode_erpToWoo']) ? $options_new_values['schedule_mode_erpToWoo'] : 'manual';
    $sync_time_interval_erp_to_woo_old_value = isset($options_old_values['sync_time_interval_erp_to_woo']) ? $options_old_values['sync_time_interval_erp_to_woo'] : '150';
    $sync_time_interval_erp_to_woo_new_value = isset($options_new_values['sync_time_interval_erp_to_woo']) ? $options_new_values['sync_time_interval_erp_to_woo'] : '150';
    
    // self::logger
    self::logger(
      "sync_mode and sync_time_interval data changes for wooToErp and erpToWoo: \n\n"
      . "erpToWoo: \n"
      . "erpToWoo_sync_mode_old_value:  $erpToWoo_sync_mode_old_value  \n"
      . "erpToWoo_sync_mode_new_value: $erpToWoo_sync_mode_new_value \n"
      . "sync_time_interval_erp_to_woo old value: $sync_time_interval_erp_to_woo_old_value \n"
      . "sync_time_interval_erp_to_woo new value: $sync_time_interval_erp_to_woo_new_value \n\n"
      . "wooToErp: \n"
      . "wooToErp_sync_mode_old_value:  $wooToErp_sync_mode_old_value  \n"
      . "wooToErp_sync_mode_new_value: $wooToErp_sync_mode_new_value \n"
      . "sync_time_interval_woo_to_erp old value: $sync_time_interval_woo_to_erp_old_value \n"
      . "sync_time_interval_woo_to_erp new value: $sync_time_interval_woo_to_erp_new_value \n\n"
      . "sync products by category are (if any exist): " . $options_new_values['product_import_by_category']
    );
  
    // logger('Sync Mode changes: old: ' . $sync_mode_old_value . ' | new: ' . $sync_mode_new_value);
  
    // Check for wooToErp schedule mode changes
    if ($wooToErp_sync_mode_new_value !== $wooToErp_sync_mode_old_value) {
      logger('WooToErp Sync Mode change detected, changed from mode ' . $wooToErp_sync_mode_old_value . ', to mode ' . $wooToErp_sync_mode_new_value);
  
      // if changed from manual to auto: schedule a new action
      if ($wooToErp_sync_mode_old_value == 'manual' && $wooToErp_sync_mode_new_value == 'auto') {
        logger('scheduling new wooToErp action');
        self::erpsync_schedule_action($sync_time_interval_woo_to_erp_new_value, 'schedule_new_action', 'wooToErp');
      }
  
      // if changed from auto to manual: remove old scheduled action
      if ($wooToErp_sync_mode_old_value == 'auto' && $wooToErp_sync_mode_new_value == 'manual'){
        logger('UNscheduling wooToErp action');
        self::erpsync_schedule_action($sync_time_interval_woo_to_erp_new_value, 'unschedule_action', 'wooToErp');
      }
    } 

    // Check for erpToWoo schedule mode changes
    if ($erpToWoo_sync_mode_new_value !== $erpToWoo_sync_mode_old_value) {
      logger('ErpToWoo Sync Mode change detected, changed from mode ' . $erpToWoo_sync_mode_old_value . ', to mode ' . $erpToWoo_sync_mode_new_value);
  
      // if changed from manual to auto: schedule a new action
      if ($erpToWoo_sync_mode_old_value == 'manual' && $erpToWoo_sync_mode_new_value == 'auto') {
        logger('scheduling new erpToWoo action');
        self::erpsync_schedule_action($sync_time_interval_erp_to_woo_new_value, 'schedule_new_action', 'erpToWoo');
      }
  
      // if changed from auto to manual: remove old scheduled action
      if ($erpToWoo_sync_mode_old_value == 'auto' && $erpToWoo_sync_mode_new_value == 'manual'){
        logger('UNscheduling erpToWoo action');
        self::erpsync_schedule_action($sync_time_interval_erp_to_woo_new_value, 'unschedule_action', 'erpToWoo');
      }
    }

    // Check for wooToErp time interval changes (when sync mode is auto)
    if ($wooToErp_sync_mode_new_value == 'auto' && $sync_time_interval_woo_to_erp_new_value !== $sync_time_interval_woo_to_erp_old_value) {
      logger('WooToErp time interval change detected, changed from ' . $sync_time_interval_woo_to_erp_old_value . ' minutes to ' . $sync_time_interval_woo_to_erp_new_value . ' minutes');
      
      // Unschedule old action and schedule new one with updated interval
      logger('Rescheduling wooToErp action with new time interval');
      self::erpsync_schedule_action($sync_time_interval_woo_to_erp_new_value, 'unschedule_action', 'wooToErp');
      self::erpsync_schedule_action($sync_time_interval_woo_to_erp_new_value, 'schedule_new_action', 'wooToErp');
    }

    // Check for erpToWoo time interval changes (when sync mode is auto)
    if ($erpToWoo_sync_mode_new_value == 'auto' && $sync_time_interval_erp_to_woo_new_value !== $sync_time_interval_erp_to_woo_old_value) {
      logger('ErpToWoo time interval change detected, changed from ' . $sync_time_interval_erp_to_woo_old_value . ' minutes to ' . $sync_time_interval_erp_to_woo_new_value . ' minutes');
      
      // Unschedule old action and schedule new one with updated interval
      logger('Rescheduling erpToWoo action with new time interval');
      self::erpsync_schedule_action($sync_time_interval_erp_to_woo_new_value, 'unschedule_action', 'erpToWoo');
      self::erpsync_schedule_action($sync_time_interval_erp_to_woo_new_value, 'schedule_new_action', 'erpToWoo');
    }
  
  }
  
  /**
   * Summary of erpsync_schedule_action
   * @param $time_interval int (minutes)
   * @param mixed $action fn()
   * @param string $direction wooToErp or erpToWoo
   * @return void
   */
  private static function erpsync_schedule_action($time_interval, $action, $direction = 'wooToErp') {
    logger('action is ' . $action . ' for direction: ' . $direction);
  
    // Check if Action Scheduler is already loaded (by WooCommerce or another plugin)
    if (!class_exists('ActionScheduler')) {
      // If not, include it from your plugin
      require_once plugin_dir_path(__FILE__) . 'vendor/action-scheduler/action-scheduler.php';
    }
  
    // Determine the action hook based on direction
    $action_hook = ($direction == 'erpToWoo') ? 'perform_sync_erp_to_woo_hook' : 'perform_sync_woo_to_erp_hook';

    if ($action == 'unschedule_action') {
      
      // as_unschedule_action( $hook, $args, $group );
      as_unschedule_action($action_hook);
      logger('action ' . $action_hook . ' unscheduled for ' . $direction);
  
    } elseif ($action == 'schedule_new_action') {
      // if ($action == 'schedule_new_action'){
      // $action_id = as_schedule_single_action($next_run, 'perform_erp_sync');
  
    
      // $erp_to_woo = new ERPtoWoo();
  
      // $action_id = as_schedule_recurring_action(
      //     $next_run,         // First run time (timestamp)
      //     $interval,         // Interval in seconds
      //     // 'perform_erp_sync' // Action hook
      //     'perform_sync_erp_to_woo_hook' // Action hook
      // );

  
      // Convert time_interval from minutes to seconds
      $interval_seconds = $time_interval * 60;
      
      // Schedule first run in 30 seconds
      $next_run = time() + 30;
        
      // Schedule recurring action using proper interval and hook
      $action_id = as_schedule_recurring_action(
        $next_run,           // When to first run
        $interval_seconds,   // How often to rerun (in seconds)
        $action_hook         // The hook to execute
      );
      
      // Daily at certain hour
      // $action_id = as_schedule_recurring_action(
      //   $next_run,       // When to first run
      //   10, // DAY_IN_SECONDS,   // How often to rerun (daily); interval in seconds
      //   'perform_erp_sync'  // The hook to execute
      // );
      
      
      logger('scheduled ' . $direction . ' action with id: ' . $action_id . ' | time now: ' . time() . ' | scheduled time: ' . $next_run . ' | interval: ' . $time_interval . ' minutes (' . $interval_seconds . ' seconds)');
      logger( 'Action hook: ' . $action_hook . ' for direction: ' . $direction);
  
    // Enqueue an action to run one time, as soon as possible.
    // $id = as_enqueue_async_action('perform_erp_sync');
    // logger('sheduled action id is ' . $id);
    }
  }

  private static function logger( $message ) {
      UserNotice::log_message( '[ERPsync_Action_Scheduler] ' . $message);
  }

}

// // register hook for sync function callback, to be detected and run by action-schduler
// function perform_erp_sync_callback() {
//   logger('ERP Sync executed via Action Scheduler at ' . date('Y-m-d H:i:s'));
  
//   // Call your actual sync function
//   if (function_exists('perform_erp_sync')) {
//       perform_erp_sync();
//   } else {
//       include_once(WP_PLUGIN_DIR . '/ERP-Sync/includes/sync/sync-logic.php');
//       perform_erp_sync();
//   }
// }
// add_action('perform_erp_sync', 'perform_erp_sync_callback');

add_action('perform_sync_erp_to_woo_hook', function() {
  ERPtoWoo::perform_sync_erp_to_woo();
});

add_action('perform_sync_woo_to_erp_hook', function() {
  require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_woo_to_erp.php';
  
  // Call the new WooToErp sync function
  WooToErp::perform_sync_woo_to_erp();
});