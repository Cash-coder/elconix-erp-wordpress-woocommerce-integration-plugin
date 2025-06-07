<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class ERPtoWoo {

  /**
   * Main function for ERP -> Woo integration. If there are IDs it will import those, otherwise it import all products
   * @param mixed $options wp_settings_api get_options()
   * @return bool success/fail
   */


  public static function perform_sync_erp_to_woo() {
   
    $options = get_option('plugin_erpsync');
    // if import_by_id have IDs: import ONLY those products, otherwise import all products (because if IDs are specified its safe to assume that the user only wants to import those products and not all of them)
    $ids = $options['product_import_by_id'];
    if ($ids){
      $response = ImportById::erp_import($options); //IDs included in $options
      if ($response) {
        // exit function with success flag
        return true;
      } else {
        // exit function with error flag
        return false;
      }
    } else {
      $response = ERPtoWoo::import_all_erp_products($options);
      if ($response) return true;
      return false;
    }
  }

  /**
   * while loop to import_all_erp_products: GET products, check responses, import to woo
   * @param $products json
   * @return bool
   */
  public static function import_all_erp_products($options) {
    
    // while 5 calls, get prods, check responses, import to woo
    
    $api_calls_limit = 20; // 25 prods by call
    $api_call_number = 0;
    $api_error_number = 0;

    // import products   
    $products_total_processed = 0;
    $products_imported_successfully = 0;

    while ( $api_call_number < $api_calls_limit ) {
      $api_call_number++;

      $request_body = [
        'class'  => 'GET',
        'action' => 'products',
        'page'   => $api_call_number, // use api call number as pagination
      ];

      $erp_response = ERPtoWoo::make_erp_request($request_body, $options);

      // check wp errors
      $wp_error = ERPtoWoo::erp_check_wp_errors($erp_response);
      if ($wp_error['error'] == true) {
        self::logger('wp error detected: '. $wp_error['error_message']);
        $api_error_number++;
        continue;
      }

      // check http errors
      $http_error = ERPtoWoo::erp_check_http_errors($erp_response);
      if ($http_error['error'] == true) {
        self::logger('http error detected: '. $http_error['error_message']);
        $api_error_number++;
        continue;
      }

      // no errors, get products json
      $body = wp_remote_retrieve_body($erp_response);
      $decoded_response = json_decode($body, true);

      // import products with foreach loop
      if ( $decoded_response ) {
        if (isset($decoded_response['products'])) {
          foreach ($decoded_response['products'] as $product) {
            $products_total_processed++;

            $woo_response = ERPtoWoo::create_woo_product($product);
            
            if ($woo_response) {
              $products_imported_successfully++; 
            } else { // error
              self::logger('woo response importing product: '. $woo_response);
            }
          }
        }
      }
    } // end of while loop

    self::logger('Importados con éxito ' . $products_imported_successfully . '/' . $products_total_processed . ' productos.');
    UserNotice::admin_notice_message('success' ,'Importados con éxito ' . $products_imported_successfully . '/' . $products_total_processed . ' productos.');
    
    // success
    return true;

    // import just 5 products to test
    // $products = array_slice($products, 0, 5);
    // $products = [];
    // foreach ($products as $product) {
    //   $products_total_processed ++;
    //   $response = self::create_woo_product($product);
      
    //   // count success/total
    //   if ($response) $products_imported_successfully++ ;
    // }
  }

  /**
   * create_woo_product with WC_Product_Simple api
   * @param $product_data json
   * @return bool true/false success/failure
   */
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

  /**
   * Summary of make_erp_request
   * @param $body request body
   * @param $options wp_settings_api $options = get_options()
   * @return {body: string, cookies: WP_Http_Cookie[], filename: string|null, headers: WpOrg\Requests\Utility\CaseInsensitiveDictionary, http_response: WP_HTTP_Requests_Response, response: array{code: int, message: string}|WP_Error}
   */
  public static function make_erp_request($body, $options) {

    $args = self::set_api_args($body, $options['api_key']);
    
    $response = wp_remote_post( $options['api_url'], $args );

    return $response;


    // attempt x times to make the request
    // $max_attempts = 2;
    // $attempt = 0;

    // while ($attempt <= $max_attempts) { 
      
    //   $attempt++;

      // self::logger('Http Request Attempt number: ' . $attempt . '/' . $max_attempts);

      // Make the request
      // $response = wp_remote_post( $options['api_url'], $args );
      // self::logger('raw response: ' . print_r($response, true));
      // self::logger('response: ' . $response['response']['code']);
      
      // check for errors
      // $errors = self::erp_check_wp_errors($response);
      // self::logger('wp errors: ' . $errors);

    //   if (is_wp_error($response)) {
    //     return $response;

    //   } elseif ($errors['error'] == 'timeout') {
    //     continue;

    //   } else {
    //     return $errors['error'];
    //   }
    // }
    
    // error, return errors
    // if ($attempt > $max_attempts) {
    //   return $errors['error'];
    // }

    // return json_decode(wp_remote_retrieve_body($body), true);
  }

  /**
   * checks http errors from a request response
   * @param $response from a wp_remote_post()
   * @return {error: bool, error_type: string}
   */
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
        return ['error' => true, 'error_type' => 'timeout'];
    }
    // other type of wp errors
      self::logger('API/WP ERROR: ' . $response->get_error_message());
      // return true;
      return ['error' => true, 'error_type' => $response->get_error_message()];
    } 

    // return false;
    return ['error' => false];
 
  }

  /**
   * Sets arguments, headers and body for request with wp_remote_post()
   * @param $request_body {action : products, page : 1}
   * @param $api_key from wp_settings_api get_options() 
   * @return $args {headers:headers, body:body, arg1: arg1}
   */
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
        'timeout' => 40, // fix timeout bug
        'headers' => $headers,
        'body'    => wp_json_encode($request_body), 
    ];  
  } 

  /**
   * request just to test HTTP (api, api_key, ip, ...) and Wordpress errors (timeout)
   * @param $options array from wp_settings_api, get_options()
   * @return {error: bool, error_message: string}
   */
  public static function erp_test_connection($options){

    $request_body = [
        'class'  => 'GET',
        'action' => 'products',
        'page'   => '1',
    ];
    
    // make request, get Response Code and body
    self::logger('testing ERP connection ...');
    $response = self::make_erp_request($request_body, $options);
    
    $wp_error = self::erp_check_wp_errors($response);
    if ($wp_error['error'] == true) {
    return ['error' => true, 'error_message' => 'Wordpress server error: ' . $wp_error['error_type'] . ' - Inténtelo de nuevo más tarde.'];
    }

    $http_error = self::erp_check_http_errors($response);
    if ($http_error['error']) {
      return ['error' => true, 'error_message' => $http_error['error_message']];
    }

    // no http errors
    self::logger('ERP connection successful!');
    return ['error' => false];

  }

  /**
   * @param mixed $response array from wp_remote_post()
   * @return array{error: bool, error_message: string|array{error: bool, response_code: int|string}}
   * @return {error: bool, error_message: str}
   */
  public static function erp_check_http_errors($response) {
    $response_code = wp_remote_retrieve_response_code($response);
    $wp_message = wp_remote_retrieve_response_message($response);
    // $response_body = wp_remote_retrieve_body($response);

    if ($response_code === 404)   {

      self::logger('detected HTTP error with code: ' . $response_code);
      return ['error' => true,'error_message'=> 'Error 404: La URL de la API no existe'];

    } elseif ($response_code === 401) {

      self::logger('detected HTTP error with code: ' . $response_code);
      return ['error' => true, 'error_message'=> 'Error 401: Acceso no Autorizado: API Key o IP inválida - ' . $wp_message];

    } elseif ($response_code === 500) {

      self::logger('detected HTTP error with code: ' . $response_code);
        return ['error' => true, 'error_message'=> 'Error 500 en la API de Elconix' ];
    }

    return ['error'=> false,'response_code'=> $response_code];
  }
  
  // Utility function for logging
  private static function logger($message) {
    UserNotice::log_message( '[ERPtoWoo] ' . $message);
  }
}
