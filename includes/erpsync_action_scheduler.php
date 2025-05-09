<?php

// SCHEDULER HANDLER
function erpsync_scheduler_handler($old_values, $new_values, $option_name) {
  // This function will run AFTER click on "Guardar Cambios" button
  // but BEFORE the page redirects back to the settings page
  
  // foreach ($old_value as $value) {
  //   error_log($value);
  // }
  // foreach ($new_value as $value) {
  //   error_log($value);
  // }

  $old_value = $old_values['schedule_mode_wooToErp'];
  $new_value = $new_values['schedule_mode_wooToErp'];

  // error_log('Sync Mode changes: old: ' . $old_value . ' | new: ' . $new_value);

  // if the schedule settings were changed
  if ($new_value !== $old_value) {
    error_log('Sync Mode change detected, changed from mode ' . $old_value . ', to mode ' . $new_value);

    // trigger the un/schedule action fn
    // if changed from manual to auto: schedule a new action
    if ($old_value == 'manual' && $new_value == 'auto') {

      error_log('scheduling new action');
      erpsync_schedule_action($new_value, 'schedule_new_action');

    }

    // if changed from auto to manual: remove old scheduled action
    if ($old_value == 'auto' && $new_value == 'manual'){
      error_log('UNscheduling action');
      erpsync_schedule_action($new_value, 'unschedule_action');
    }

    // one fn with argument add schedule,time or remove schedule,T
  }
  
}

function erpsync_schedule_action($mode, $action) {
  error_log('action is ' . $action);

  // Check if Action Scheduler is already loaded (by WooCommerce or another plugin)
  if (!class_exists('ActionScheduler')) {
    // If not, include it from your plugin
    require_once plugin_dir_path(__FILE__) . 'vendor/action-scheduler/action-scheduler.php';
  }

  if ($action == 'unschedule_action') {
    
    // as_unschedule_action( $hook, $args, $group );
    as_unschedule_action('perform_erp_sync'); 
    error_log('action perform_erpsync unscheduled');

  } elseif ($action == 'schedule_new_action') {
      // if ($action == 'schedule_new_action'){
    // $action_id = as_schedule_single_action($next_run, 'perform_erp_sync');

    // Schedule the next sync
    $next_run = time() + 10;
    
    $action_id = as_schedule_recurring_action(
      $next_run,       // When to first run
      10, // DAY_IN_SECONDS,   // How often to rerun (daily); interval in seconds
      'perform_erp_sync'  // The hook to execute
    );
    
    

    error_log('scheduled action with id: ' . $action_id . ' | time now: ' . time() . ' | scheduled time: ' . $next_run);
    $options = get_option('plugin_erpsync');
    error_log( 'XXXX ' . $options['schedule_time_wooToErp']);

  // Enqueue an action to run one time, as soon as possible.
  // $id = as_enqueue_async_action('perform_erp_sync');
  // error_log('sheduled action id is ' . $id);
  }
}


// register hook for sync function callback, to be detected and run by action-schduler
function perform_erp_sync_callback() {
  error_log('ERP Sync executed via Action Scheduler at ' . date('Y-m-d H:i:s'));
  
  // Call your actual sync function
  if (function_exists('perform_erp_sync')) {
      perform_erp_sync();
  } else {
      include_once(WP_PLUGIN_DIR . '/ERP-Sync/includes/sync-logic.php');
      perform_erp_sync();
  }
}
add_action('perform_erp_sync', 'perform_erp_sync_callback');