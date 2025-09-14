<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/erpsync_import_by_category.php';

class ERPtoWoo {

  /**
   * Main function for ERP -> Woo integration. If there are IDs (import product by ID) or categories (import product by category) it will import those, otherwise it import all products
   * @param mixed $options wp_settings_api get_options()
   * @return bool success/fail
   */
  public static function perform_sync_erp_to_woo() {
   
    $options = get_option('plugin_erpsync');
    // if import_by_id have IDs: import ONLY those products, otherwise import all products (because if IDs are specified its safe to assume that the user only wants to import those products and not all of them)
    $ids = $options['product_import_by_id'];
    $categories= $options['product_import_by_category'];

    if ($ids || $categories) {
      $response_ids = false;
      $response_categories = false;
      
      if ($ids) {
        $response_ids = ImportById::erp_import($options); //IDs included in $options
      }
      
      if ($categories) {
        $response_categories = erpsync_import_by_category::import_by_category($options);
      }
      
      // if both responses are false, return false
      if (!$response_ids && !$response_categories) {
        return false;
      }
      
      // if one of the responses is true, return true
      if ($response_ids || $response_categories) {
        return true;
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
    
    // $api_calls_limit = 2; // 25 prods by call
    $api_calls_limit = 99; // 25 prods by call 
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
        // Check stock quantity from "Rebajamoda via España" warehouse
        $stock_quantity = 0;
        if (isset($product_data['InStock']) && is_array($product_data['InStock'])) {
            foreach ($product_data['InStock'] as $warehouse) {
                if ($warehouse['WareHouse'] === 'Rebajamoda via España') {
                    $stock_quantity = (int)$warehouse['Available'];
                    break;
                }
            }
        }
        
        // Skip product creation if no stock available in that warehouse
        if ($stock_quantity <= 0) {
            $product_id = $product_data['Producto']['id'] ?? 'unknown';
            $product_name = $product_data['Producto']['Nombre'] ?? 'unknown';
            self::logger("product ID $product_id with name $product_name not created due to 0 stock in warehouse Rebajamoda via España");
            return false;
        }
        
        $product = new WC_Product_Simple();
        $product->set_name($product_data['Producto']['Nombre'] ?? '');
        $product->set_sku($product_data['Producto']['id'] ?? '');
        $product->set_regular_price($product_data['Producto']['Precio_Venta'] ?? 1);
        
        // Set stock quantity and management
        $product->set_stock_quantity($stock_quantity);
        $product->set_manage_stock(true);  
        $product->set_stock_status('instock');
        
        // Handle category hierarchy
        $category_id = self::create_category_hierarchy($product_data);
        if ($category_id) {
          $product->set_category_ids([$category_id]);
          self::logger("Assigned product to category ID: $category_id");
        }
        
        $product_id = $product->save();
        self::logger('created woo product with ID: ' . $product_id . ', stock: ' . $stock_quantity);
        
        // Handle product images
        if (isset($product_data['Images']) && is_array($product_data['Images']) && !empty($product_data['Images'])) {
            $image_ids = self::process_product_images($product_data['Images'], $product_id);
            if (!empty($image_ids)) {
                $product->set_image_id($image_ids[0]); // Set first image as main image
                if (count($image_ids) > 1) {
                    $product->set_gallery_image_ids(array_slice($image_ids, 1)); // Set rest as gallery
                }
                $product->save();
                self::logger('Added ' . count($image_ids) . ' images to product ID: ' . $product_id);
            }
        }
        
        return true;
    } catch (Exception $e) {
        $error_message = $e->getMessage();

        // if error message is ~"duplicated SKU" write other log message
        if (strpos($error_message, 'Invalid or duplicated SKU') !== false) {
            self::logger("Product with ID $product_id not created - matching product already exists in WooCommerce");
        } else {
            self::logger("Product creation failed: " . $error_message);
        }
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
  
  /**
   * Create category hierarchy and return the deepest category ID
   * @param array $product_data ERP product data
   * @return int|null Category ID or null if no categories
   */
  private static function create_category_hierarchy($product_data) {
    $categories = [];
    $parent_id = 0;
    
    // Extract categories from ERP data
    for ($i = 1; $i <= 3; $i++) {
      $category_key = "Category_L$i";
      if (isset($product_data['Producto'][$category_key]) && !empty($product_data['Producto'][$category_key])) {
        $categories[] = ucwords(strtolower($product_data['Producto'][$category_key]));
      }
    }
    
    if (empty($categories)) {
      return null;
    }
    
    // Create hierarchy: each category becomes child of previous
    foreach ($categories as $category_name) {
      $term = term_exists($category_name, 'product_cat', $parent_id);
      
      if (!$term) {
        // Category doesn't exist, create it
        $term = wp_insert_term(
          $category_name,
          'product_cat',
          array('parent' => $parent_id)
        );
        
        if (is_wp_error($term)) {
          self::logger("Failed to create category '$category_name': " . $term->get_error_message());
          continue;
        }
        
        $parent_id = $term['term_id'];
        self::logger("Created category '$category_name' with ID: $parent_id");
      } else {
        $parent_id = $term['term_id'];
        self::logger("Found existing category '$category_name' with ID: $parent_id");
      }
    }
    
    return $parent_id;
  }
  
  /**
   * Process and import product images from ERP to WordPress media library
   * @param array $images Array of image data from ERP API
   * @param int $product_id WooCommerce product ID
   * @return array Array of attachment IDs
   */
  private static function process_product_images($images, $product_id) {
    $attachment_ids = [];
    
    foreach ($images as $image_data) {
      if (!isset($image_data['src']) || empty($image_data['src'])) {
        continue;
      }
      
      $image_url = $image_data['src'];
      self::logger("Processing image: $image_url");
      
      // Check if image already exists for this product
      $existing_attachment = self::get_existing_attachment_by_url($image_url, $product_id);
      if ($existing_attachment) {
        $attachment_ids[] = $existing_attachment;
        self::logger("Using existing image attachment ID: $existing_attachment");
        continue;
      }
      
      // Download and upload image
      $attachment_id = self::upload_image_from_url($image_url, $product_id);
      if ($attachment_id) {
        $attachment_ids[] = $attachment_id;
        self::logger("Successfully uploaded image with attachment ID: $attachment_id");
      } else {
        self::logger("Failed to upload image: $image_url");
      }
    }
    
    return $attachment_ids;
  }
  
  /**
   * Upload image from URL to WordPress media library
   * @param string $image_url URL of the image
   * @param int $product_id WooCommerce product ID
   * @return int|false Attachment ID on success, false on failure
   */
  private static function upload_image_from_url($image_url, $product_id) {
    // Include required WordPress functions
    if (!function_exists('media_handle_sideload')) {
      require_once(ABSPATH . 'wp-admin/includes/media.php');
      require_once(ABSPATH . 'wp-admin/includes/file.php');
      require_once(ABSPATH . 'wp-admin/includes/image.php');
    }
    
    try {
      // Download image to temporary file using WordPress function
      $temp_file = download_url($image_url, 30);
      
      if (is_wp_error($temp_file)) {
        self::logger("Failed to download image: " . $temp_file->get_error_message());
        return false;
      }
      
      // Get file extension from URL
      $file_extension = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
      if (empty($file_extension)) {
        $file_extension = 'jpg'; // Default fallback
      }
      
      // Create filename
      $filename = 'erp-product-' . $product_id . '-' . uniqid() . '.' . $file_extension;
      
      // Prepare file array for media_handle_sideload
      $file_array = array(
        'name'     => $filename,
        'tmp_name' => $temp_file,
      );
      
      // Upload to media library using sideload (designed for external files)
      $attachment_id = media_handle_sideload($file_array, $product_id);
      
      // Clean up temp file
      if (file_exists($temp_file)) {
        unlink($temp_file);
      }
      
      if (is_wp_error($attachment_id)) {
        self::logger("Media sideload failed: " . $attachment_id->get_error_message());
        return false;
      }
      
      // Store original URL in attachment meta for future reference
      update_post_meta($attachment_id, '_erp_original_url', $image_url);
      
      return $attachment_id;
      
    } catch (Exception $e) {
      self::logger("Exception during image upload: " . $e->getMessage());
      return false;
    }
  }
  
  /**
   * Check if an image attachment already exists for this URL and product
   * @param string $image_url Original image URL
   * @param int $product_id Product ID
   * @return int|false Attachment ID if found, false otherwise
   */
  private static function get_existing_attachment_by_url($image_url, $product_id) {
    global $wpdb;
    
    $attachment_id = $wpdb->get_var($wpdb->prepare("
      SELECT post_id 
      FROM {$wpdb->postmeta} 
      WHERE meta_key = '_erp_original_url' 
      AND meta_value = %s
      AND post_id IN (
        SELECT ID FROM {$wpdb->posts} 
        WHERE post_parent = %d 
        AND post_type = 'attachment'
      )
      LIMIT 1
    ", $image_url, $product_id));
    
    return $attachment_id ? (int)$attachment_id : false;
  }
  
  /**
   * Get file extension from MIME type
   * @param string $mime_type MIME type
   * @return string File extension
   */
  private static function get_extension_from_mime_type($mime_type) {
    $mime_types = array(
      'image/jpeg' => 'jpg',
      'image/jpg'  => 'jpg',
      'image/png'  => 'png',
      'image/gif'  => 'gif',
      'image/webp' => 'webp',
      'image/svg+xml' => 'svg'
    );
    
    return isset($mime_types[$mime_type]) ? $mime_types[$mime_type] : 'jpg';
  }
  
  // Utility function for logging
  private static function logger($message) {
    UserNotice::log_message( '[ERPtoWoo] ' . $message);
  }
}
