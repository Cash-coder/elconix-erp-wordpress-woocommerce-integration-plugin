<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';

class ImportById {
  public static function erp_import($options){
 
    $ids = $options['product_import_by_id'];
    self::logger('IDs to import: ' . $ids);

    // transform from comma separated values into a list
    $ids = array_map('trim', explode(',', $ids));

    foreach ($ids as $id) {
      self::logger('Importing ID: ' . $id);

      //{“class”:”GET”,”action”:”products”,”id”:”PS0000003″}
      $request_body = [
        'class'  => 'GET',
        'action' => 'products',
        'id'   => $id,
      ];

      $decoded_data = ERPtoWoo::make_erp_request($request_body, $options);
      
      if ( $decoded_data ) {
        if (isset($decoded_data['products'])) {
          $response = ERPtoWoo::create_woo_product($decoded_data['products'][0]);
        }
      } else {
        self::logger('no products available or JSON decoded data available');
        continue;
      }    
    }

    // success
    return true;    
  }

  private static function logger($message){
    UserNotice::log_message('[ImportById] ' . $message);
  }
}