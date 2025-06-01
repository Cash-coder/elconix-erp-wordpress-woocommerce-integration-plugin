<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class ERPtoWoo {
  public static function sync_test($options) {
   
    $decoded_data = self::get_products($options);

    // if products
    if ( $decoded_data ) {
      if (isset($decoded_data['products'])) {
        UserNotice::print_all_products($decoded_data, $stock=false);
        self::import_products($decoded_data['products']);
      }
    } else {
      self::log('no JSON decoded data available');
      return false;
    }

    //success
    return true;
  }

  private static function import_products($products) {
    // import products    
    $total_count = 0;
    $success_count = 0;
    
    $products = array_slice($products, 0, 6);
    foreach ($products as $product) {
      $total_count ++;
      $response = self::create_woo_product($product);
      
      // count success/total
      if ($response) $success_count++ ;
    }
    self::log('Importados con éxito ' . $success_count . '/' . $total_count . ' productos.');
    UserNotice::admin_notice_message('success' ,'Importados con éxito ' . $success_count . '/' . $total_count . ' productos.');
    // sleep(5);
  }

  private static function create_woo_product($product_data) {
    try {
        $product = new WC_Product_Simple();
        $product->set_name($product_data['Producto']['Nombre'] ?? '');
        $product->set_sku($product_data['Producto']['Item_Number'] ?? '');
        $product->set_regular_price($product_data['Producto']['Precio_Venta'] ?? 1);
        self::log($product->save());
        return true;
    } catch (Exception $e) {
        self::log("Product creation failed: " . $e->getMessage());
        return false;
    }
}

  private static function get_products($options) {

    // Headers
    $headers = [
      'Content-Type' => 'application/json',
      'X-ENX-Token'  => $options['api_key'],
    ];

    // Body (JSON)
    $body = [
        'class'  => 'GET',
        'action' => 'products',
        'page'   => '1',
    ];

    // Args for wp_remote_post()
    $args = [
        'headers' => $headers,
        'body'    => wp_json_encode($body), // WordPress-safe JSON encoding
    ];
    
    // Make the request
    $response = wp_remote_post( $options['api_url'], $args );
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    // Handle HTTP errors (4xx, 5xx)
    if ($response_code >= 400) {
      self::log("ERP API Error ($response_code): " . $response_body);
      
      if ($response_code = 400)   {
        UserNotice::admin_notice_message('error', 'Error 404: La URL de la API no existe');
      } elseif ($response_code = 500){
        UserNotice::admin_notice_message('error', 'Error 500 en la API');
      }
      return false;
    } 
    
    // Check if WP_Error (e.g., timeout, connection failed)
    if (is_wp_error($response)) {
      self::log('API/WP ERROR: ' . $response->get_error_message());
      return false;
    } 
    // Otherwise, log the full response (including body, headers, status)
    else {
      // error_log('API RESPONSE: ' . print_r($response, true));
    }
    
    return json_decode(wp_remote_retrieve_body($response), true);

  }

  // Utility function for logging
  private static function log($message) {
    UserNotice::log_message( '[ERPtoWoo] ' . $message);
  }
}
