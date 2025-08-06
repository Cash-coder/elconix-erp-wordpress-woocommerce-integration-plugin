<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';

class erpsync_import_by_category {

  public static function import_by_category($options){
    
    $categories = $options['product_import_by_category'];

    self::logger("Import By category triggered, categories to import are: \n" . $categories);
    
    // Parse user input and transform to API filters
    $filters = self::parse_categories_input($categories);
    
    // Debug: log original and transformed input
    self::logger("Original input: " . $categories);
    self::logger("Transformed filters: " . json_encode($filters, JSON_PRETTY_PRINT));
    
    if (empty($filters)) {
      self::logger("No valid categories found to import");
      return false;
    }
    
    // Build request body with filters
    $request_body = [
      'class' => 'GET',
      'action' => 'products',
      'filters' => $filters
    ];
    
    // Make API request
    $erp_response = ERPtoWoo::make_erp_request($request_body, $options);
    
    // Check wp errors
    $wp_error = ERPtoWoo::erp_check_wp_errors($erp_response);
    if ($wp_error['error'] == true) {
      self::logger('wp error detected: '. $wp_error['error_message']);
      return false;
    }

    // Check http errors
    $http_error = ERPtoWoo::erp_check_http_errors($erp_response);
    if ($http_error['error'] == true) {
      self::logger('http error detected: '. $http_error['error_message']);
      return false;
    }

    // No errors, get products json
    $body = wp_remote_retrieve_body($erp_response);
    $decoded_response = json_decode($body, true);
    
    $products_imported_successfully = 0;
    $products_total_processed = 0;
    
    if ($decoded_response && isset($decoded_response['products'])) {
      // foreach ($decoded_response['products'] as $product) { // production: all products
      foreach ($decoded_response['products'] as $product) { // testing: limit to 10 products
        $products_total_processed++;
        
        // Testing limit: stop after 10 products
        if ($products_total_processed > 10) 
          break;
        
        $woo_response = ERPtoWoo::create_woo_product($product);
        
        if ($woo_response) {
          $products_imported_successfully++; 
        } else {
          self::logger('woo response importing product: '. $woo_response);
        }
      }
    }
    
    self::logger('Importados con éxito ' . $products_imported_successfully . '/' . $products_total_processed . ' productos por categoría.');
    
    return $products_imported_successfully > 0;
  }
  
  /**
   * Parse user input like "L1: hogar, L2: linea blanca" into API filters
   * @param string $categories_input
   * @return array API filters array
   */
  private static function parse_categories_input($categories_input) {
    $filters = [];
    
    if (empty($categories_input)) {
      return $filters;
    }
    
    // Split by comma to get individual category entries
    $category_entries = array_map('trim', explode(',', $categories_input));
    
    foreach ($category_entries as $entry) {
      // Check if entry contains ':'
      if (strpos($entry, ':') !== false) {
        // Split by ':' to separate field and value
        $parts = explode(':', $entry, 2);
        $field_part = trim($parts[0]);
        $value_part = strtoupper(trim($parts[1]));
        
        // Build the field name (Category_L1, Category_L2, etc.)
        $field_name = 'Category_' . $field_part;
        
        $filters[] = [
          'field' => $field_name,
          'type' => '=',
          'value' => $value_part
        ];
      }
    }
    
    return $filters;
  }
  
  private static function logger($message){
    UserNotice::log_message('[ImportByCategory] ' . $message);
  }
}