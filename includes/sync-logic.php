<?php
// Sync function
function perform_erp_sync() {
  error_log('------------running sync------------');
  // $options = get_option('erp_sync_toggles', []);
  $options = get_option('plugin_erpsync');

  // foreach ($options as $option) {error_log($option);}
  error_log($options['license_key']);
  

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