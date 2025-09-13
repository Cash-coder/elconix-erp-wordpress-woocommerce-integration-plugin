<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class WooToErp {
    
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
            // Check if customer exists in ERP
            $customer_email = $order->get_billing_email();
            self::logger('Processing order #' . $order->get_id() . ' with email: "' . $customer_email . '"');

            if (empty($customer_email)) {
                self::logger('WARNING: Order #' . $order->get_id() . ' has empty billing email - skipping customer check');
                self::log_single_order($order);
                continue;
            }

            $existing_customer = self::get_customer_by_email($customer_email);

            if (!$existing_customer) {
                // Customer doesn't exist, create new one
                self::logger('Creating new customer for email: ' . $customer_email);
                $customer_phone = $order->get_billing_phone();
                $customer_country = $order->get_billing_country();

                $new_customer = self::create_customer($customer_email, $customer_phone, $customer_country);

                if ($new_customer) {
                    self::logger('Customer created successfully');
                } else {
                    self::logger('ERROR: Failed to create customer for order #' . $order->get_id());
                    continue; // Skip this order if customer creation failed
                }
            } else {
                $customer_id = isset($existing_customer['Cliente']) ? $existing_customer['Cliente'] : 'N/A';
                self::logger('Using existing customer ID: ' . $customer_id . ' for email: ' . $customer_email);
            }

            // Log the order details
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
            
            // Get product categories and subcategories for this specific product
            $categories = '';
            if ($product) {
                $category_ids = $product->get_category_ids();
                $category_hierarchy = array(); // subcategories
                foreach ($category_ids as $category_id) {
                    $category = get_term($category_id, 'product_cat');
                    if ($category && !is_wp_error($category)) {
                        // Build category hierarchy (parent > child)
                        $hierarchy = array();
                        $current_category = $category;
                        
                        // Build hierarchy from child to parent
                        while ($current_category) {
                            array_unshift($hierarchy, $current_category->name);
                            if ($current_category->parent) {
                                $current_category = get_term($current_category->parent, 'product_cat');
                            } else {
                                break;
                            }
                        }
                        
                        $category_hierarchy[] = implode(' > ', $hierarchy);
                    }
                }
                $categories = !empty($category_hierarchy) ? implode(', ', $category_hierarchy) : 'N/A';
            } else {
                $categories = 'N/A';
            }
            
            self::logger('  - ' . $item->get_name() . ' (Qty: ' . $item->get_quantity() . ', Price: ' . $item->get_total() . ' ' . $order->get_currency() . ', Categories: ' . $categories . ')');
        }
        
        self::logger('-------- END ORDER #' . $order->get_id() . ' --------');
    }

    /**
     * Get customer ID from ERP by email
     */
    public static function get_customer_by_email($email) {
        $query_array = [
            "class" => "GET",
            "action" => "customers",
            "filters" => [
                [
                    "field" => "Email",
                    "type" => "=",
                    "value" => $email
                ]
            ]
        ];
        $query = json_encode($query_array, JSON_UNESCAPED_SLASHES);

        $response = self::make_erp_api_request($query);

        if ($response && isset($response['customers']) && !empty($response['customers'])) {
            self::logger('Customer found with email: ' . $email);
            return $response['customers'][0];
        }

        self::logger('No customer found with email: ' . $email);
        return false;
    }

    /**
     * Create new customer in ERP
     */
    public static function create_customer($email, $phone = '', $country = '') {
        $query_array = [
            "class" => "PUT",
            "action" => "customers",
            "data" => [
                "Email" => $email,
                "Cellular" => $phone,
                "Pais" => $country
            ]
        ];
        $query = json_encode($query_array, JSON_UNESCAPED_SLASHES);

        $response = self::make_erp_api_request($query);

        if ($response && isset($response['response']) && $response['response']['response'] === 'Success') {
            $customer_id = $response['response']['id'];
            $token = $response['response']['Token'];

            self::logger('Customer created successfully - ID: ' . $customer_id);
            return $response['response'];
        }

        self::logger('Failed to create customer with email: ' . $email);
        return false;
    }

    /**
     * Make API request to ERP
     */
    private static function make_erp_api_request($query) {
        // Get plugin options for API settings
        $options = get_option('plugin_erpsync');
        $api_url = isset($options['api_url']) ? $options['api_url'] : '';
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';

        if (empty($api_url) || empty($api_key)) {
            self::logger('ERROR: API URL or API Key not configured');
            return false;
        }

        // Prepare the request
        $headers = array(
            'Content-Type' => 'application/json',
            'X-ENX-Token' => $api_key
        );

        $args = array(
            'body' => $query,
            'headers' => $headers,
            'method' => 'POST',
            'timeout' => 40
        );

        // self::logger('Making API request to: ' . $api_url);
        self::logger('Request data: ' . $query);

        // Make the request
        $response = wp_remote_post($api_url, $args);
        // self::logger('Full response array: ' . print_r($response, true));

        if (is_wp_error($response)) {
            self::logger('API request error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        self::logger('API response code: ' . $response_code);
        self::logger('API response body: ' . $response_body);

        if ($response_code >= 200 && $response_code < 300) {
            if (empty($response_body)) {
                return false;
            }

            $decoded_response = json_decode($response_body, true);
            return $decoded_response;
        } else {
            self::logger('API request failed with code: ' . $response_code);
            return false;
        }
    }
}