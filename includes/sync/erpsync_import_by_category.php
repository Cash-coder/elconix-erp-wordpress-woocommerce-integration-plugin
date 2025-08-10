<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';

class erpsync_import_by_category {

  public static function import_by_category($options){
    
    $categories = $options['product_import_by_category'];

    self::logger("Import By category triggered, categories to import are: \n" . $categories);
    
    // Parse user input and get array of category filter sets
    $category_filter_sets = self::parse_categories_input($categories);
    
    // Debug: log original and transformed input
    self::logger("Original input: " . $categories);
    self::logger("Parsed category sets: " . json_encode($category_filter_sets, JSON_PRETTY_PRINT));
    
    if (empty($category_filter_sets)) {
      self::logger("No valid categories found to import");
      return false;
    }
    
    $total_products_imported_successfully = 0;
    $total_products_processed = 0;
    
    // Make separate API call for each category set
    foreach ($category_filter_sets as $index => $filters) {
      self::logger("Processing category set " . ($index + 1) . "/" . count($category_filter_sets));
      
      // Build request body with filters for this category set
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
        self::logger('wp error detected for category set ' . ($index + 1) . ': '. $wp_error['error_message']);
        continue;
      }

      // Check http errors
      $http_error = ERPtoWoo::erp_check_http_errors($erp_response);
      if ($http_error['error'] == true) {
        self::logger('http error detected for category set ' . ($index + 1) . ': '. $http_error['error_message']);
        continue;
      }

      // No errors, get products json
      $body = wp_remote_retrieve_body($erp_response);
      $decoded_response = json_decode($body, true);
      
      $products_imported_successfully = 0;
      $products_total_processed = 0;
      
      if ($decoded_response && isset($decoded_response['products'])) {
        foreach ($decoded_response['products'] as $product) {
          $products_total_processed++;
          
          // Testing limit: stop after 10 products per category
          // if ($products_total_processed > 10) 
          //   break;
          
          $woo_response = ERPtoWoo::create_woo_product($product);
          
          if ($woo_response) {
            $products_imported_successfully++; 
          } else {
            self::logger('woo response importing product: '. $woo_response);
          }
        }
      }
      
      self::logger('Category set ' . ($index + 1) . ': Importados con éxito ' . $products_imported_successfully . '/' . $products_total_processed . ' productos.');
      
      $total_products_imported_successfully += $products_imported_successfully;
      $total_products_processed += $products_total_processed;
    }
    
    self::logger('TOTAL: Importados con éxito ' . $total_products_imported_successfully . '/' . $total_products_processed . ' productos por categoría.');
    
    // Admin notice to show user feedback
    UserNotice::admin_notice_message('success', 'Importados con éxito ' . $total_products_imported_successfully . '/' . $total_products_processed . ' productos por categorías');
    
    return $total_products_imported_successfully > 0;
  }
  
  /**
   * Parse user input with multiple category sets like:
   * '{L1: damas, L2: accesorios, L3: billetera}, {L1: CABALLEROS, L2: ACCESORIOS, L3: RELOJ}'
   * @param string $categories_input
   * @return array Array of filter sets, each set is an array of filters for one complete category
   */
  private static function parse_categories_input($categories_input) {
    $filter_sets = [];
    
    if (empty($categories_input)) {
      return $filter_sets;
    }
    
    // Clean up input - normalize whitespace but preserve structure
    $categories_input = trim($categories_input);
    
    // Use regex to find all curly brace objects in the input
    $pattern = '/\{[^{}]*\}/';
    preg_match_all($pattern, $categories_input, $matches);
    
    if (!empty($matches[0])) {
      // Process each found object
      foreach ($matches[0] as $object_string) {
        $filters = self::parse_single_category_set(trim($object_string));
        
        if (!empty($filters)) {
          $filter_sets[] = $filters;
          self::logger("Parsed category set: " . json_encode($filters));
        }
      }
    }
    
    self::logger("Total filter sets created: " . count($filter_sets));
    return $filter_sets;
  }
  
  /**
   * Parse a single category set string like: {L1: damas, L2: accesorios, L3: billetera}
   * Supports both quoted JSON and unquoted key-value pairs
   * @param string $object_string
   * @return array Array of filters for this category set
   */
  private static function parse_single_category_set($object_string) {
    $filters = [];
    
    // Remove outer braces
    $content = trim($object_string, '{}');
    
    // First try to parse as proper JSON
    $json_test = '{' . $content . '}';
    $decoded = json_decode($json_test, true);
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
      // Valid JSON - process normally
      foreach ($decoded as $field => $value) {
        $filters[] = self::create_filter_from_field_value($field, $value);
      }
    } else {
      // Not valid JSON - parse manually for unquoted keys
      // Split by commas first
      $pairs = explode(',', $content);
      
      foreach ($pairs as $pair) {
        $pair = trim($pair);
        if (strpos($pair, ':') !== false) {
          list($key, $value) = array_map('trim', explode(':', $pair, 2));
          // Remove any quotes from key and value
          $key = trim($key, '"\'');
          $value = trim($value, '"\'');
          
          if (!empty($key) && !empty($value)) {
            $filters[] = self::create_filter_from_field_value($key, $value);
          }
        }
      }
    }
    
    return array_filter($filters); // Remove any null entries
  }
  
  /**
   * Create a filter array from field name and value
   * @param string $field
   * @param string $value
   * @return array|null Filter array or null if invalid
   */
  private static function create_filter_from_field_value($field, $value) {
    $category_field = '';
    
    // Check if it's the old format (Category_L1, Category_L2, Category_L3)
    if (in_array($field, ['Category_L1', 'Category_L2', 'Category_L3'])) {
      $category_field = $field;
    }
    // Check if it's the new simplified format (L1, L2, L3)
    else if (in_array($field, ['L1', 'L2', 'L3'])) {
      $category_field = 'Category_' . $field;
    }
    
    if (!empty($category_field) && !empty($value)) {
      return [
        'field' => $category_field,
        'type' => '=',
        'value' => strtoupper(trim($value))
      ];
    }
    
    return null;
  }
  
  private static function logger($message){
    UserNotice::log_message('[ImportByCategory] ' . $message);
  }
}