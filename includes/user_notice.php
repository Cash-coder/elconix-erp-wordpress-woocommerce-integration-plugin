<?php
class UserNotice {

  public static function log_message($message) {
    $log_file = ERP_SYNC_PLUGIN_DIR . 'erp_sync.log';
    // $timestamp = date('[Y-m-d UTC] ');
    $timestamp = date('[Y-m-d H:i:s] ');
    $formatted_message = $timestamp . $message . PHP_EOL;
    
    // Ensure the file is writable and create if it doesn't exist
    file_put_contents($log_file, $formatted_message, FILE_APPEND | LOCK_EX);
  }

  public static function admin_notice_message($type, $message){
    
    // single notice
    // set_transient('erp_sync_notice', [
    //   'type' => $type, //success/'error'/'warning'/'info'
    //   'message' => $message
    // ], 45);

    // Get existing notices (if any)
    $notices = get_transient('erp_sync_notice') ?: [];

    // Append the new notice
    $notices[] = [
        'type'    => $type, // 'success'/'error'/'warning'/'info'
        'message' => $message
    ];

    // Save back to transient (expires in 45 seconds)
    set_transient('erp_sync_notice', $notices, 45);
  }
  
  public static function print_all_products($decoded_data, $stock=false){
      foreach ($decoded_data['products'] as $product) {
        self::log_message("-------- PRODUCT START --------");
        
        // Log Product Details
        if (isset($product['Producto'])) {
            foreach ($product['Producto'] as $key => $value) {
                self::log_message("$key: $value");
            }
        }
        // if stock variable is true, print warehouses too
        if ($stock) {
          if (isset($product['InStock'])) {
            self::log_message("\nSTOCK INFO:");
            foreach ($product['InStock'] as $stock) {
                foreach ($stock as $key => $value) {
                    self::log_message("$key: $value");
                }
                self::log_message("---"); // Separator between warehouses
            }
          }
        }
        if (isset($product['PriceLists'])) {
          self::log_message("\nPRICE LISTS:");
          foreach ($product['PriceLists'] as $price) {
              foreach ($price as $key => $value) {
                  self::log_message("$key: $value");
              }
          }
      }
    }
  }

  public static function transient_error($error){
    // When error occurs:
    set_transient('erp_api_error', $error, 30); // Stores for 30 seconds

    // Then display wherever needed (e.g., in admin notices):
    if ($error = get_transient('erp_api_error')) {
        echo '<div class="notice notice-error is-dismissible">
                <pre>'.esc_html(print_r($error, true)).'</pre>
              </div>';
        delete_transient('erp_api_error');
    }
  }

  public static function api_error($response) {
    self::log_message('ERPtoWoo sync: Invalid API response - ' . print_r($response, true));
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
  public static function display_admin_notices() {
    $notices = get_transient('erp_sync_notice');
  
    if (!empty($notices)) {
        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
        }
  
        // Clear after displaying
        delete_transient('erp_sync_notice');
    }
  }
}


// Hook into admin notices
add_action('admin_notices', ['UserNotice', 'display_admin_notices']);