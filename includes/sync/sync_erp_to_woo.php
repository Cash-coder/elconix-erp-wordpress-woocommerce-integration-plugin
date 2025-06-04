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
    
    // set body of request to get all products
    $request_body = [
      'class'  => 'GET',
      'action' => 'products',
      'page'   => '1',
    ];
    
    $response = self::make_erp_request($request_body, $options);
    // if error,handle error, notice, stop
    

    // import all products
    if ( $response ) {
      if (isset($response['products'])) {
        // UserNotice::print_all_products($response, $stock=false);
        self::import_all_erp_products($response['products']);
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

    // attempt x times to make the request
    $max_attempts = 2;
    $attempt = 0;

    while ($attempt <= $max_attempts) { 
      
      $attempt++;

      // self::logger('Http Request Attempt number: ' . $attempt . '/' . $max_attempts);

      // Make the request
      $response = wp_remote_post( $options['api_url'], $args );
      // self::logger('raw response: ' . print_r($response, true));
      // self::logger('response: ' . $response['response']['code']);
      
      // check for errors
      $errors = self::erp_check_wp_errors($response);
      // self::logger('wp errors: ' . $errors);

      // return result if all is fine,
      if (!$errors) {
        // return json_decode(wp_remote_retrieve_body($response), true);
        return $response;

      } elseif ($errors['error'] == 'timeout') {
        continue;

      } else {
        return $errors['error'];
      }
    }
    
    // error, return errors
    if ($attempt > $max_attempts) {
      return $errors['error'];
    }

    // return json_decode(wp_remote_retrieve_body($body), true);
  }

  public static function erp_check_wp_errors($response){
    
    // fake mock responses to test WP_Error handling logic
    // $response = new WP_Error();
    // $response->add('http_request_failed', 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received');
    
    // Check if WP_Error (e.g., timeout, connection failed)
    if (is_wp_error($response)) {
      
      // timeout error
      if (strpos($response->get_error_message(), 'timed out') !== false 
        || strpos($response->get_error_message(), 'cURL error 28') !== false
      ) {
        self::logger('Timeout Error Detected: ' . $response->get_error_message());
        
        // return true;
        return ['error' => 'timeout'];
    }
    // other type of wp errors
      self::logger('API/WP ERROR: ' . $response->get_error_message());
      // return true;
      return ['error' => $response->get_error_message()];
    } 

    // return false;
    return ['error' => false];
 
  }

  public static function set_api_args($request_body, $api_key) {

    // Headers
    $headers = [
        'Content-Type' => 'application/json',
        'X-ENX-Token'  => $api_key,
    ];
    
    // request_body (JSON)
    // $request_body = [
    //     'class'  => 'GET',
    //     'action' => 'products',
    //     'page'   => '1',
    // ];
    
    // Args for wp_remote_post()
    return $args = [
        'timeout'     => 30,
        'headers' => $headers,
        'body'    => wp_json_encode($request_body), // WordPress-safe JSON encoding
    ];  
  } 

  public static function erp_test_connection($options){

    $request_body = [
        'class'  => 'GET',
        'action' => 'products',
        'page'   => '1',
    ];
    
    // make request, get Response Code and body
    self::logger('testing ERP connection ...');
    $response = self::make_erp_request($request_body, $options);
    $response_code = $response['response']['response']['code'];
    $response_body = $response['body'];

    if ($response['success']) {
          //Handle HTTP errors (4xx, 5xx)
    if ($response_code >= 400) {
      self::logger("ERP API Error ($response_code): " . $response_body);
      
      if ($response_code === 404)   {
        // UserNotice::admin_notice_message('error', 'Error 404: La URL de la API no existe');
        // return false;
        return ['error' => 'error','message'=> 'Error 404: La URL de la API no existe'];
      } elseif ($response_code === 401) {
        // UserNotice::admin_notice_message('error', 'Error 401: Acceso no Autorizado: API Key o IP inválida - ' . $response_body);
        // return false;
        return ['error' => 'error', 'message'=> 'Error 401: Acceso no Autorizado: API Key o IP inválida - ' . $response_body];
      } elseif ($response_code === 500){
        // UserNotice::admin_notice_message('error', 'Error 500 en la API de Elconix');
        // return false;
        return ['error' =>'error', 'message'=> 'Error 500 en la API de Elconix' ];
        }
      } 
    }

    // self::logger('from erp_test_connection, response: ' . $response['response']);

    // $wp_error = self::erp_check_wp_errors($response);
    
    // $response_code = $response['response']['code'];
    $response_code = wp_remote_retrieve_response_code($response['response']);
    $response_body = wp_remote_retrieve_body($response);

    self::logger('response_code is : ' . $response_code);

    // self::logger(
    //   'response is : '. print_r($response, true) 
    //   . ' response code is: ' . $response_code 
    //   . ' response body is: '. $response_body
    // );

    //HTTP is fine
    self::logger('ERP connection successful!');
    return ['error' => false];

  }
  
  // Utility function for logging
  private static function logger($message) {
    UserNotice::log_message( '[ERPtoWoo] ' . $message);
  }
}
