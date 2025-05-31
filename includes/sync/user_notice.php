di<?php
class UserNotice {
  
  public static function print_all_products($decoded_data, $stock=false){
      foreach ($decoded_data['products'] as $product) {
        error_log("-------- PRODUCT START --------");
        
        // Log Product Details
        if (isset($product['Producto'])) {
            foreach ($product['Producto'] as $key => $value) {
                error_log("$key: $value");
            }
        }
        // if stock variable is true, print warehouses too
        if ($stock) {
          if (isset($product['InStock'])) {
            error_log("\nSTOCK INFO:");
            foreach ($product['InStock'] as $stock) {
                foreach ($stock as $key => $value) {
                    error_log("$key: $value");
                }
                error_log("---"); // Separator between warehouses
            }
          }
        }
        if (isset($product['PriceLists'])) {
          error_log("\nPRICE LISTS:");
          foreach ($product['PriceLists'] as $price) {
              foreach ($price as $key => $value) {
                  error_log("$key: $value");
              }
          }
      }
    }
  }

  public static function transient_error($error){
    // When error occurs:
    set_transient('erp_api_error', $response, 30); // Stores for 30 seconds

    // Then display wherever needed (e.g., in admin notices):
    if ($error = get_transient('erp_api_error')) {
        echo '<div class="notice notice-error is-dismissible">
                <pre>'.esc_html(print_r($error, true)).'</pre>
              </div>';
        delete_transient('erp_api_error');
    }
  }
  public static function api_error($response) {
    error_log('ERPtoWoo sync: Invalid API response - ' . print_r($response, true));
    echo '<div class="api-error-notice">
      <p> Error desde la API</p>
      <pre>' . esc_html( print_r($response, true) ) . '</pre>
      </div>
      <style>
        .api-error-notice {
          background: #f8d7da;
          border: 1px solid #f5c6cb;
          border-radius: 4px;
          color: #721c24;
          padding: 15px;
          margin: 20px 0;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .api-error-notice pre {
          background: rgba(0,0,0,0.05);
          padding: 10px;
          border-radius: 3px;
          overflow-x: auto;
          white-space: pre-wrap;
          margin: 10px 0;
        }
      </style>';
  }

  public static function show_progress($response) {
    echo '<div class="user_notice>USER NOTICE></div>';
  }
}