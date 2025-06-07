<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/check_license.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/import_by_id.php';

// Sync function
function perform_erp_sync() {
  logger('------------ Sync Start ------------');
  
  $options = get_option('plugin_erpsync');
  // foreach ($options as $option) {logger($option);}
 
  // get license key
  $license_key = $options['license_key'];
  logger('License key provided by the user is : ' . $license_key);

  // Check license validity, if wrong: error message + exit func
  if (!License::check_license($license_key)) {
    logger('license key invalid, sync function stopped');
    UserNotice::admin_notice_message('error', 'Clave de licencia inválida');
    return false;
  }

  // Test connection with erp
  $connection_test = ERPtoWoo::erp_test_connection($options);
  if ($connection_test['error']) {
    logger('error in ERP test: ' . $connection_test['error_message']);
    UserNotice::admin_notice_message('error',$connection_test['error_message']);
    return false;    
  }

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
  
  // if woo to ERP sync is enabled   *******************************
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
      logger('product sync enabled');

      // if import_by_id is active it will import ONLY those products
      // logger('sync prods by id:');
      // $response_by_id = ImportById::import();
      
      $response = ERPtoWoo::perform_sync_erp_to_woo();
      // if error
      if (!$response) {
        return false;
      }
    }

  }

  UserNotice::admin_notice_message('success', 'Sincronización completada con éxio');
  
  logger('**************** Sync End ****************');

  return true;
}

// Utility function for logging
function logger($message) {
  UserNotice::log_message( '[sync-logic] ' . $message);
}

