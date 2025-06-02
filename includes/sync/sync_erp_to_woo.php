<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class ERPtoWoo {
  public static function sync_test($options) {
   
    $decoded_data = self::get_products($options);

    // if products
    if ( $decoded_data ) {
      if (isset($decoded_data['products'])) {
        // UserNotice::print_all_products($decoded_data, $stock=false);
        self::import_products($decoded_data['products']);
      }
    } else {
      self::logger('no JSON decoded data available');
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
    self::logger('Importados con éxito ' . $success_count . '/' . $total_count . ' productos.');
    UserNotice::admin_notice_message('success' ,'Importados con éxito ' . $success_count . '/' . $total_count . ' productos.');
    sleep(5);
  }

  private static function create_woo_product($product_data) {
    try {
        $product = new WC_Product_Simple();
        $product->set_name($product_data['Producto']['Nombre'] ?? '');
        $product->set_sku($product_data['Producto']['Item_Number'] ?? '');
        $product->set_regular_price($product_data['Producto']['Precio_Venta'] ?? 1);
        self::logger($product->save());
        return true;
    } catch (Exception $e) {
        self::logger("Product creation failed: " . $e->getMessage());
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

    // attempt 3 times to make the request
    $max_attempts = 3;
    $attempt = 1;

    while ($attempt <= $max_attempts) { 
      
      self::logger('Product Request Attempt number: ' . $attempt);

      // Make the request
      $response = wp_remote_post( $options['api_url'], $args );
      $response_code = wp_remote_retrieve_response_code($response);
      $response_body = wp_remote_retrieve_body($response);

      // fake mock responses to test error handling logic
      // $response = new WP_Error();
      // $response->add('http_request_failed', 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received');
      
      // Handle HTTP errors (4xx, 5xx)
      if ($response_code >= 400) {
        self::logger("ERP API Error ($response_code): " . $response_body);
        
        if ($response_code === 404)   {
          UserNotice::admin_notice_message('error', 'Error 404: La URL de la API no existe');
        } elseif ($response_code === 401) {
          UserNotice::admin_notice_message('error', 'Error 401: Acceso no Autorizado. API Key o IP inválida');
        } elseif ($response_code === 500){
          UserNotice::admin_notice_message('error', 'Error 500 en la API');
        }
        
        $attempt++;
        sleep(2);

        continue;
      } 

      // Check if WP_Error (e.g., timeout, connection failed)
      if (is_wp_error($response)) {
        
        $attempt++;
        
        // notice for timeout error
        if (strpos($response->get_error_message(), 'timed out') !== false 
          || strpos($error_message, 'cURL error 28') !== false
        ) {
          self::logger('Timeout Error Detected: ' . $response->get_error_message());
          
          if ( $attempt > $max_attempts) {
            self::logger('Max attempts number reached, aborting program');
            UserNotice::admin_notice_message('error', 'Time Out Error: La conexión tardó demasiado, intentelo de nuevo más tarde.');
            return false;
          }
          continue;
        }

        self::logger('API/WP ERROR: ' . $response->get_error_message());

        sleep(2);
        continue;
      } 
      // Otherwise, log the full response (including body, headers, status)
      else {
        // logger('API RESPONSE: ' . print_r($response, true));
      }
      
      $attempt++;

      // all is fine, return products
      self::logger('Request Successful!');
      return json_decode(wp_remote_retrieve_body($response), true);

    }
    
    if ($attempt > $max_attempts) {
      return false;
    }

  }

  private static function check_errors($response) {

  }

  // Utility function for logging
  private static function logger($message) {
    UserNotice::log_message( '[ERPtoWoo] ' . $message);
  }
}
