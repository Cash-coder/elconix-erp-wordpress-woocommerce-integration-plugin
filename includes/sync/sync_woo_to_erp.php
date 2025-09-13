<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class WooToErp {
    
    /**
     * Dummy function to test wooToErp sync functionality
     * This function will be triggered by the action scheduler
     */
    public static function perform_sync_woo_to_erp() {
        // Log that the function is being triggered
        UserNotice::log_message('[WooToErp] sync_woo_to_erp being triggered at ' . date('Y-m-d H:i:s'));
        
        // Get all completed orders
        $orders = self::get_all_completed_orders();
        
        // Sync orders to ERP
        self::sync_orders_to_erp($orders);
        
        // Log completion
        UserNotice::log_message('[WooToErp] sync_woo_to_erp completed');
        
        return true;
    }

    private static function logger($message){
        UserNotice::log_message('[WooToERP] ' . $message);
    }

    /**
     * Get all WooCommerce orders with 'completed' status
     */
    public static function get_all_completed_orders() {
        self::logger('Starting to retrieve all completed orders...');
        
        // Get all orders with 'completed' status
        $orders = wc_get_orders(array(
            'status' => 'completed',
            'limit' => -1, // Get all orders
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($orders)) {
            self::logger('No completed orders found');
            return array();
        }
        
        self::logger('Found ' . count($orders) . ' completed orders');
        return $orders;
    }

    /**
     * Sync orders to ERP - traverse all orders and log their data
     */
    public static function sync_orders_to_erp($orders) {
        if (empty($orders)) {
            self::logger('No orders to sync');
            return;
        }
        
        self::logger('Starting to sync ' . count($orders) . ' orders to ERP');
        
        foreach ($orders as $order) {
            self::log_single_order($order);
        }
        
        self::logger('Successfully synced ' . count($orders) . ' orders to ERP');
    }
    
    /**
     * Log essential data for a single order
     */
    private static function log_single_order($order) {
        self::logger('-------- ORDER #' . $order->get_id() . ' --------');
        
        // Order basic info
        self::logger('Order ID: ' . $order->get_id());
        self::logger('Total: ' . $order->get_total() . ' ' . $order->get_currency());
        
        // Customer data - Billing
        self::logger('BILLING INFO:');
        self::logger('  Name: ' . trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()));
        self::logger('  Email: ' . $order->get_billing_email());
        self::logger('  Phone: ' . $order->get_billing_phone());
        self::logger('  Address: ' . $order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
        self::logger('  City: ' . $order->get_billing_city());
        self::logger('  State: ' . $order->get_billing_state());
        self::logger('  Postcode: ' . $order->get_billing_postcode());
        self::logger('  Country: ' . $order->get_billing_country());
        
        // Customer data - Shipping
        self::logger('SHIPPING INFO:');
        self::logger('  Name: ' . trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()));
        self::logger('  Address: ' . $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2());
        self::logger('  City: ' . $order->get_shipping_city());
        self::logger('  State: ' . $order->get_shipping_state());
        self::logger('  Postcode: ' . $order->get_shipping_postcode());
        self::logger('  Country: ' . $order->get_shipping_country());
        
        // Products
        self::logger('PRODUCTS:');
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            
            // Get product categories for this specific product
            $categories = '';
            if ($product) {
                $category_ids = $product->get_category_ids();
                $category_names = array();
                foreach ($category_ids as $category_id) {
                    $category = get_term($category_id, 'product_cat');
                    if ($category && !is_wp_error($category)) {
                        $category_names[] = $category->name;
                    }
                }
                $categories = !empty($category_names) ? implode(', ', $category_names) : 'N/A';
            } else {
                $categories = 'N/A';
            }
            
            self::logger('  - ' . $item->get_name() . ' (Qty: ' . $item->get_quantity() . ', Price: ' . $item->get_total() . ' ' . $order->get_currency() . ', Categories: ' . $categories . ')');
        }
        
        self::logger('-------- END ORDER #' . $order->get_id() . ' --------');
    }
}