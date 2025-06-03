<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';

class ImportById {
  public static function erp_import($options){
 
    $ids = $options['product_import_by_id'];
    self::logger('IDs to import: ' . $ids);

    // transform from comma separated values into a list
    $ids = array_map('trim', explode(',', $ids));

    $total_count = count($ids);
    $success_count = 0;

    foreach ($ids as $id) {
      self::logger('Importing ID: ' . $id);

      //{“class”:”GET”,”action”:”products”,”id”:”PS0000003″}
      $request_body = [
        'class'  => 'GET',
        'action' => 'products',
        'id'   => $id,
      ];

      $erp_response = ERPtoWoo::make_erp_request($request_body, $options);
      // check error, notice, stop
      // if ($response[0] == 'error') {
      //   if (isset($response['error']) && $response['error']) {
        
      //   //if its a timeout, continue
      //   if (strpos($response['error'], 'Timeout') !== false ) {
      //     continue;
      //   } else {
          
      //     // notice message to user
      //     UserNotice::admin_notice_message('error', $response['error']);
          
      //     //stop execution
      //     return false;
      //   }
      // }
      
      if ( $erp_response ) {
        if (isset($erp_response['products'])) {
          $woo_response = ERPtoWoo::create_woo_product($erp_response['products'][0]);
          // if no error, success +1
          if ($woo_response) $success_count++;

          continue;
        }
      } else {
        self::logger('no products available or no JSON decoded data available from response');
        
        continue;
      }  
    } // end foreach

    // admin notice "importados x/y productos"
    UserNotice::admin_notice_message('success', 'Importados con éxito ' . $success_count . '/' . $total_count . ' productos');

    // success
    return true;    
  }

  private static function logger($message){
    UserNotice::log_message('[ImportById] ' . $message);
  }
}