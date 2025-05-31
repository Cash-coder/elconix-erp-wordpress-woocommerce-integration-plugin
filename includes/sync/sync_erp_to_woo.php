<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class ERPtoWoo {
    public static function sync_test($options) {

        $api_key = $options['api_key'];
        $api_url = $options['api_url'];

        $decoded_data = self::get_products($options);

        // Use the data
        if ( $decoded_data ) {
          // log the prods
          if (isset($decoded_data['products'])) {
            UserNotice::print_all_products($decoded_data, $stock=false);
            
            // import products
            $products = array_slice($decoded_data['products'],0, 6);
            foreach ($products as $product) {
              self::create_woo_product($product);
            }
          }
        } else {
          self::log_message('no JSON decoded data available');
        }
    }

    private static function create_woo_product($product_data) {
      try {
          $product = new WC_Product_Simple();
          $product->set_name($product_data['Producto']['Nombre'] ?? '');
          $product->set_sku($product_data['Producto']['Item_Number'] ?? '');
          $product->set_regular_price($product_data['Producto']['Precio_Venta'] ?? 1);
          self::log_message($product->save());
          return true;
      } catch (Exception $e) {
          self::log_message("Product creation failed: " . $e->getMessage());
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
    
    // Check if WP_Error (e.g., timeout, connection failed)
    if (is_wp_error($response)) {
      self::log_message('API ERROR: ' . $response->get_error_message());
      
      // UserNotice::api_error($response);
      set_transient('erp_api_error', $response, 30); // Stores for 30 seconds

      // Then display wherever needed (e.g., in admin notices):
      if ($error = get_transient('erp_api_error')) {
          echo '<div class="notice notice-error is-dismissible">
                  <pre>'.esc_html(print_r($error, true)).'</pre>
                </div>';
          delete_transient('erp_api_error');
      }
    } 
    // Otherwise, log the full response (including body, headers, status)
    else {
      // error_log('API RESPONSE: ' . print_r($response, true));
    }
    
    return json_decode(wp_remote_retrieve_body($response), true);

  }

  // Utility function for logging
  private static function log_message($message) {
    error_log("[ERPtoWoo] " . $message);
  }
}
