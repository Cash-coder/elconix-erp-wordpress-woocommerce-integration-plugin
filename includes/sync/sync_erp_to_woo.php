<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class ERPtoWoo {
  public static function sync_test($options) {
   
    // if import_by_id have IDs: import ONLY those products, otherwise import all products
    $ids = $options['product_import_by_id'];
    if ($ids){
      $response_by_id = ImportById::erp_import($options); //IDs included in $options
      if ($response_by_id) {
        // exit function with success flag
        return true;
      } else {
        // exit function with error flag
        return false;
      }
    }

    // set one body or other 
    
    // get products
    $request_body = [
      'class'  => 'GET',
      'action' => 'products',
      'page'   => '1',
    ];
    
    $decoded_data = self::make_erp_request($request_body, $options);

    // import all products
    if ( $decoded_data ) {
      if (isset($decoded_data['products'])) {
        // UserNotice::print_all_products($decoded_data, $stock=false);
        self::import_all_erp_products($decoded_data['products']);
      }
    } else {
      self::logger('no products available or JSON decoded data available');
      return false;
    }

    //success
    return true;
  }

  public static function import_all_erp_products($products) {
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
  }

  public static function import_erp_single_product($product) {

    $response = self::create_woo_product($product);
      
    self::logger('Importados con éxito ' . $success_count . '/' . $total_count . ' productos.');
  }

  public static function create_woo_product($product_data) {
    try {
        $product = new WC_Product_Simple();
        $product->set_name($product_data['Producto']['Nombre'] ?? '');
        $product->set_sku($product_data['Producto']['Item_Number'] ?? '');
        $product->set_regular_price($product_data['Producto']['Precio_Venta'] ?? 1);
        self::logger('created woo product with ID: ' . $product->save());
        return true;
    } catch (Exception $e) {
        self::logger("Product creation failed: " . $e->getMessage());
        return false;
    }
  }

  public static function make_erp_request($body, $options) {

    $args = self::set_api_args($body, $options['api_key']);

    // attempt 3 times to make the request
    $max_attempts = 3;
    $attempt = 1;

    while ($attempt <= $max_attempts) { 
      
      self::logger('Product Request Attempt number: ' . $attempt);

      // Make the request
      $response = wp_remote_post( $options['api_url'], $args );
      $response_code = wp_remote_retrieve_response_code($response);
      $response_body = wp_remote_retrieve_body($response);

      // fake mock responses to test WP_Error handling logic
      // $response = new WP_Error();
      // $response->add('http_request_failed', 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received');
      
      //Handle HTTP errors (4xx, 5xx)
      if ($response_code >= 400) {
        self::logger("ERP API Error ($response_code): " . $response_body);
        
        $attempt++;

        if ($response_code === 404 && $attempt > $max_attempts)   {
          UserNotice::admin_notice_message('error', 'Error 404: La URL de la API no existe');
        } elseif ($response_code === 401 && $attempt > $max_attempts) {
          UserNotice::admin_notice_message('error', 'Error 401: Acceso no Autorizado: API Key o IP inválida - ' . $response_body);
        } elseif ($response_code === 500 && $attempt > $max_attempts){
          UserNotice::admin_notice_message('error', 'Error 500 en la API de Elconix');
        }
        
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

      // all is fine, return result / products
      self::logger('Request Successful!');
      return json_decode(wp_remote_retrieve_body($response), true);

    }
    
    if ($attempt > $max_attempts) {
      return false;
    }

  }

  public static function set_api_args($body, $api_key) {

    // Headers
    $headers = [
        'Content-Type' => 'application/json',
        'X-ENX-Token'  => $api_key,
    ];
    
    // Body (JSON)
    // $body = [
    //     'class'  => 'GET',
    //     'action' => 'products',
    //     'page'   => '1',
    // ];
    
    // Args for wp_remote_post()
    return $args = [
        'headers' => $headers,
        'body'    => wp_json_encode($body), // WordPress-safe JSON encoding
    ];  
  } 

  // Utility function for logging
  private static function logger($message) {
    UserNotice::log_message( '[ERPtoWoo] ' . $message);
  }
}
