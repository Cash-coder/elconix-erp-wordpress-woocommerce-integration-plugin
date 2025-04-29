<?php
/**
 * Plugin Name: ERP Sync
 * Description: Sync WooCommerce data with ERP system
 * Version: 1.0
 */


 if (!defined('ABSPATH')) exit;

 
 // Define plugin constants
 define('ERP_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
 define('ERP_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));
 
 // Include the settings page
 require_once ERP_SYNC_PLUGIN_DIR . 'includes/settings-page.php';
 
 // add admin menu icon link
 add_action('admin_menu', 'add_ERP_menu_to_admin_sidebar');
 
 function add_ERP_menu_to_admin_sidebar() {
     add_menu_page(
         'ERP-Sync',                 // Page title (browser tab)
         'ERP Sync',                 // Menu title (displayed in sidebar)
         'manage_options',           // Capability required (admin-level access)
         'erp-sync',                 // Menu slug (URL parameter)
         'erp_sync_admin_page',      // Callback function to render the page
         'dashicons-randomize',  // Icon (Dashicon class)
         80                         // Position (lower number = higher placement)
     );
 }
 
 // Optional: Define the callback function for the page content
 function erp_sync_admin_page() {
     echo '<div class="wrap">';
     echo '<h1>ERP-Sync Settings</h1>';
     echo '<form method="post" action="options.php">';
     
     settings_fields('erp_sync_options_group');
     do_settings_sections('erp-sync');
     submit_button('Save Settings');
     
     echo '</form>';
     echo '</div>';
 }
 
 
 add_action('init', function() {
   error_log('AAAAAA - PHP error_log');
 });
 
 
 add_action('init', function() {
   $args = array('orderby' => 'ID', 'limit' => -1);
   $prods = wc_get_products($args); 
   
   foreach ( $prods as $product ) {
     $name = $product->get_name() . "<br>";
     // error_log($product);
     // echo $product->get_name() . "<br>";
   }
 });
 


/*
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

$woocommerce = new Client(
    'https://your-store-url.com',
    'your_consumer_key',
    'your_consumer_secret',
    [
        'version' => 'wc/v3',
    ]
);

try {
    $orders = $woocommerce->get('orders', [
        'per_page' => 20,
        'page' => 1,
        'after' => '2023-01-01T00:00:00',
        'status' => 'processing',
    ]);
    
    echo "Retrieved " . count($orders) . " orders\n\n";
    
    foreach ($orders as $order) {
        echo "Order #{$order->id} - {$order->date_created}\n";
        echo "Status: {$order->status}\n";
        echo "Customer: {$order->billing->first_name} {$order->billing->last_name}\n";
        echo "Total: {$order->total} {$order->currency}\n";
        
        echo "Items:\n";
        foreach ($order->line_items as $item) {
            echo "- {$item->quantity}x {$item->name} ({$item->product_id}): {$item->total}\n";
        }
        
        echo "\n----------------------------\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
*/

?>