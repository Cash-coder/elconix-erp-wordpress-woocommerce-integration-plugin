<?php
/**
 * ERP to WooCommerce Integration
 * 
 * This file implements the synchronization of variable products and inventory
 * between an external ERP system and WooCommerce.
 */

class ERP_WooCommerce_Integration {
    // ERP API configuration
    private $erp_api_base_url = 'https://api.example-erp.com/v1/';
    private $erp_api_key = 'YOUR_ERP_API_KEY';
    
    // Configuration settings
    private $sync_interval = 3600; // In seconds (1 hour)
    private $log_enabled = true;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize hooks
        add_action('init', array($this, 'init'));
        add_action('erp_wc_sync_products', array($this, 'sync_products_from_erp'));
        add_action('erp_wc_sync_inventory', array($this, 'sync_inventory_from_erp'));
        add_action('woocommerce_product_set_stock', array($this, 'sync_inventory_to_erp'));
        
        // Schedule cron jobs if not already scheduled
        if (!wp_next_scheduled('erp_wc_sync_products')) {
            wp_schedule_event(time(), 'hourly', 'erp_wc_sync_products');
        }
        
        if (!wp_next_scheduled('erp_wc_sync_inventory')) {
            wp_schedule_event(time(), 'hourly', 'erp_wc_sync_inventory');
        }
    }

    /**
     * Initialize the integration
     */
    public function init() {
        $this->log('Integration initialized');
    }

    /**
     * Make an API request to the ERP system
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array $data Request data
     * @return array|WP_Error Response data or error
     */
    private function erp_api_request($endpoint, $method = 'GET', $data = array()) {
        $url = $this->erp_api_base_url . ltrim($endpoint, '/');
        
        $args = array(
            'method'    => $method,
            'timeout'   => 30,
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'x-api-key'     => $this->erp_api_key
            ),
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log('API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }

    /**
     * Sync variable products from ERP to WooCommerce
     */
    public function sync_products_from_erp() {
        $this->log('Starting product synchronization from ERP');
        
        // Get products from ERP
        $erp_products = $this->erp_api_request('products', 'GET', array(
            'updated_since' => date('Y-m-d H:i:s', time() - $this->sync_interval),
            'limit' => 100
        ));
        
        if (is_wp_error($erp_products)) {
            $this->log('Failed to fetch products from ERP');
            return;
        }
        
        foreach ($erp_products as $erp_product) {
            // Check if this is a variable product
            if ($erp_product['type'] == 'variable') {
                $this->sync_variable_product($erp_product);
            }
        }
        
        $this->log('Completed product synchronization from ERP');
    }

    /**
     * Sync a single variable product from ERP to WooCommerce
     *
     * @param array $erp_product Product data from ERP
     */
    private function sync_variable_product($erp_product) {
        $this->log('Processing variable product: ' . $erp_product['sku']);

        // Check if product already exists in WooCommerce
        $product_id = wc_get_product_id_by_sku($erp_product['sku']);
        
        $product_data = array(
            'name'              => $erp_product['name'],
            'type'              => 'variable',
            'status'            => $erp_product['active'] ? 'publish' : 'draft',
            'catalog_visibility' => 'visible',
            'description'       => $erp_product['description'],
            'short_description' => $erp_product['short_description'],
            'sku'               => $erp_product['sku'],
            'regular_price'     => '',
            'virtual'           => false,
            'downloadable'      => false,
            'category_ids'      => $this->get_category_ids($erp_product['categories']),
            'tag_ids'           => $this->get_tag_ids($erp_product['tags']),
            'images'            => $this->prepare_images($erp_product['images']),
        );
        
        // Update or create the main product
        if ($product_id) {
            // Update existing product
            $product = wc_get_product($product_id);
            if ($product) {
                $product_data['id'] = $product_id;
                $this->update_product_data($product, $product_data);
            }
        } else {
            // Create new product
            $product = new WC_Product_Variable();
            $this->update_product_data($product, $product_data);
            $product_id = $product->save();
        }
        
        if (!$product_id) {
            $this->log('Failed to create/update variable product: ' . $erp_product['sku']);
            return;
        }
        
        // Process attributes
        $this->sync_product_attributes($product_id, $erp_product['attributes']);
        
        // Process variations
        $this->sync_product_variations($product_id, $erp_product['variations']);
        
        $this->log('Variable product synced successfully: ' . $erp_product['sku']);
    }

    /**
     * Synchronize product attributes for a variable product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $erp_attributes Attributes from ERP
     */
    private function sync_product_attributes($product_id, $erp_attributes) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        $attributes = array();
        
        foreach ($erp_attributes as $erp_attribute) {
            $attribute_name = wc_clean($erp_attribute['name']);
            $attribute_slug = wc_sanitize_taxonomy_name($attribute_name);
            
            // Check if this is a taxonomy-based attribute
            $taxonomy_name = 'pa_' . $attribute_slug;
            $is_taxonomy = taxonomy_exists($taxonomy_name);
            
            if ($is_taxonomy) {
                // Taxonomy-based attribute
                $attribute = new WC_Product_Attribute();
                $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy_name));
                $attribute->set_name($taxonomy_name);
                
                // Add terms
                $terms = array();
                foreach ($erp_attribute['values'] as $value) {
                    $term_name = wc_clean($value);
                    $term = get_term_by('name', $term_name, $taxonomy_name);
                    
                    if (!$term) {
                        $term = wp_insert_term($term_name, $taxonomy_name);
                        if (!is_wp_error($term)) {
                            $terms[] = $term['term_id'];
                        }
                    } else {
                        $terms[] = $term->term_id;
                    }
                }
                
                $attribute->set_options($terms);
            } else {
                // Custom attribute
                $attribute = new WC_Product_Attribute();
                $attribute->set_name($attribute_name);
                $attribute->set_options($erp_attribute['values']);
            }
            
            $attribute->set_visible($erp_attribute['visible']);
            $attribute->set_variation($erp_attribute['used_for_variation']);
            
            $attributes[] = $attribute;
        }
        
        $product->set_attributes($attributes);
        $product->save();
    }

    /**
     * Synchronize product variations for a variable product
     *
     * @param int $product_id WooCommerce product ID
     * @param array $erp_variations Variations from ERP
     */
    private function sync_product_variations($product_id, $erp_variations) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Get existing variations
        $existing_variations = $product->get_children();
        $existing_variation_skus = array();
        
        foreach ($existing_variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $existing_variation_skus[$variation_id] = $variation->get_sku();
            }
        }
        
        // Process each variation from ERP
        foreach ($erp_variations as $erp_variation) {
            $variation_sku = $erp_variation['sku'];
            $variation_id = array_search($variation_sku, $existing_variation_skus);
            
            $variation_data = array(
                'sku'           => $variation_sku,
                'regular_price' => $erp_variation['regular_price'],
                'sale_price'    => $erp_variation['sale_price'],
                'status'        => $erp_variation['active'] ? 'publish' : 'private',
                'stock_status'  => $erp_variation['stock_quantity'] > 0 ? 'instock' : 'outofstock',
                'stock_quantity' => $erp_variation['stock_quantity'],
                'manage_stock'  => true,
                'weight'        => $erp_variation['weight'],
                'dimensions'    => array(
                    'length' => $erp_variation['length'],
                    'width'  => $erp_variation['width'],
                    'height' => $erp_variation['height'],
                ),
                'image'         => isset($erp_variation['image']) ? $this->prepare_image($erp_variation['image']) : array(),
                'attributes'    => $this->prepare_variation_attributes($erp_variation['attributes']),
            );
            
            if ($variation_id) {
                // Update existing variation
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $this->update_variation_data($variation, $variation_data);
                    unset($existing_variation_skus[$variation_id]);
                }
            } else {
                // Create new variation
                $variation = new WC_Product_Variation();
                $variation->set_parent_id($product_id);
                $this->update_variation_data($variation, $variation_data);
            }
        }
        
        // Delete variations that exist in WooCommerce but not in ERP
        foreach (array_keys($existing_variation_skus) as $variation_id) {
            wp_delete_post($variation_id, true);
        }
        
        // Update product variation lookup table
        WC_Product_Variable::sync($product_id);
    }

    /**
     * Prepare variation attributes for saving
     *
     * @param array $erp_attributes Attributes from ERP
     * @return array Prepared attributes
     */
    private function prepare_variation_attributes($erp_attributes) {
        $attributes = array();
        
        foreach ($erp_attributes as $name => $value) {
            $taxonomy = wc_attribute_taxonomy_name(wc_sanitize_taxonomy_name($name));
            
            if (taxonomy_exists($taxonomy)) {
                // If it's a taxonomy-based attribute, use the term slug
                $term = get_term_by('name', $value, $taxonomy);
                if ($term) {
                    $attributes[$taxonomy] = $term->slug;
                } else {
                    $attributes[$taxonomy] = sanitize_title($value);
                }
            } else {
                // Otherwise, use the value directly
                $attributes['attribute_' . sanitize_title($name)] = sanitize_title($value);
            }
        }
        
        return $attributes;
    }

    /**
     * Update variation data
     *
     * @param WC_Product_Variation $variation WooCommerce variation object
     * @param array $data Variation data
     */
    private function update_variation_data($variation, $data) {
        if (isset($data['sku'])) {
            $variation->set_sku($data['sku']);
        }
        
        if (isset($data['regular_price'])) {
            $variation->set_regular_price($data['regular_price']);
        }
        
        if (isset($data['sale_price'])) {
            $variation->set_sale_price($data['sale_price']);
        }
        
        if (isset($data['status'])) {
            $variation->set_status($data['status']);
        }
        
        if (isset($data['stock_status'])) {
            $variation->set_stock_status($data['stock_status']);
        }
        
        if (isset($data['stock_quantity'])) {
            $variation->set_stock_quantity($data['stock_quantity']);
        }
        
        if (isset($data['manage_stock'])) {
            $variation->set_manage_stock($data['manage_stock']);
        }
        
        if (isset($data['weight'])) {
            $variation->set_weight($data['weight']);
        }
        
        if (isset($data['dimensions'])) {
            if (isset($data['dimensions']['length'])) {
                $variation->set_length($data['dimensions']['length']);
            }
            
            if (isset($data['dimensions']['width'])) {
                $variation->set_width($data['dimensions']['width']);
            }
            
            if (isset($data['dimensions']['height'])) {
                $variation->set_height($data['dimensions']['height']);
            }
        }
        
        if (isset($data['image']) && !empty($data['image'])) {
            $variation->set_image_id($data['image']['id']);
        }
        
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attribute_name => $attribute_value) {
                $variation->update_meta_data('attribute_' . $attribute_name, $attribute_value);
            }
        }
        
        $variation->save();
    }

    /**
     * Update product data
     *
     * @param WC_Product $product WooCommerce product object
     * @param array $data Product data
     */
    private function update_product_data($product, $data) {
        if (isset($data['name'])) {
            $product->set_name($data['name']);
        }
        
        if (isset($data['status'])) {
            $product->set_status($data['status']);
        }
        
        if (isset($data['catalog_visibility'])) {
            $product->set_catalog_visibility($data['catalog_visibility']);
        }
        
        if (isset($data['description'])) {
            $product->set_description($data['description']);
        }
        
        if (isset($data['short_description'])) {
            $product->set_short_description($data['short_description']);
        }
        
        if (isset($data['sku'])) {
            $product->set_sku($data['sku']);
        }
        
        if (isset($data['virtual'])) {
            $product->set_virtual($data['virtual']);
        }
        
        if (isset($data['downloadable'])) {
            $product->set_downloadable($data['downloadable']);
        }
        
        if (isset($data['category_ids'])) {
            $product->set_category_ids($data['category_ids']);
        }
        
        if (isset($data['tag_ids'])) {
            $product->set_tag_ids($data['tag_ids']);
        }
        
        if (isset($data['images']) && !empty($data['images'])) {
            $gallery_ids = array();
            
            // Set featured image
            if (isset($data['images'][0]['id'])) {
                $product->set_image_id($data['images'][0]['id']);
                
                // Add additional images to gallery
                for ($i = 1; $i < count($data['images']); $i++) {
                    if (isset($data['images'][$i]['id'])) {
                        $gallery_ids[] = $data['images'][$i]['id'];
                    }
                }
            }
            
            if (!empty($gallery_ids)) {
                $product->set_gallery_image_ids($gallery_ids);
            }
        }
        
        $product->save();
    }

    /**
     * Get WooCommerce category IDs from ERP categories
     *
     * @param array $erp_categories Categories from ERP
     * @return array WooCommerce category IDs
     */
    private function get_category_ids($erp_categories) {
        $category_ids = array();
        
        foreach ($erp_categories as $category_name) {
            $term = get_term_by('name', $category_name, 'product_cat');
            
            if (!$term) {
                $term = wp_insert_term($category_name, 'product_cat');
                if (!is_wp_error($term)) {
                    $category_ids[] = $term['term_id'];
                }
            } else {
                $category_ids[] = $term->term_id;
            }
        }
        
        return $category_ids;
    }

    /**
     * Get WooCommerce tag IDs from ERP tags
     *
     * @param array $erp_tags Tags from ERP
     * @return array WooCommerce tag IDs
     */
    private function get_tag_ids($erp_tags) {
        $tag_ids = array();
        
        foreach ($erp_tags as $tag_name) {
            $term = get_term_by('name', $tag_name, 'product_tag');
            
            if (!$term) {
                $term = wp_insert_term($tag_name, 'product_tag');
                if (!is_wp_error($term)) {
                    $tag_ids[] = $term['term_id'];
                }
            } else {
                $tag_ids[] = $term->term_id;
            }
        }
        
        return $tag_ids;
    }

    /**
     * Prepare images for WooCommerce from ERP image data
     *
     * @param array $erp_images Images from ERP
     * @return array Prepared images
     */
    private function prepare_images($erp_images) {
        $images = array();
        
        foreach ($erp_images as $erp_image) {
            $images[] = $this->prepare_image($erp_image);
        }
        
        return $images;
    }

    /**
     * Prepare a single image for WooCommerce from ERP image data
     *
     * @param array $erp_image Image from ERP
     * @return array Prepared image
     */
    private function prepare_image($erp_image) {
        // Check if image already exists in media library
        $attachment_id = $this->get_attachment_id_by_url($erp_image['url']);
        
        if (!$attachment_id) {
            // Download and import the image
            $attachment_id = $this->import_image_from_url($erp_image['url'], $erp_image['name']);
        }
        
        return array(
            'id'  => $attachment_id,
            'src' => wp_get_attachment_url($attachment_id),
            'alt' => isset($erp_image['alt']) ? $erp_image['alt'] : '',
        );
    }

    /**
     * Get attachment ID by URL
     *
     * @param string $url Image URL
     * @return int|false Attachment ID or false if not found
     */
    private function get_attachment_id_by_url($url) {
        global $wpdb;
        
        $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url));
        
        return !empty($attachment[0]) ? $attachment[0] : false;
    }

    /**
     * Import image from URL and add to media library
     *
     * @param string $url Image URL
     * @param string $name Image name
     * @return int|false Attachment ID or false if import failed
     */
    private function import_image_from_url($url, $name) {
        // Initialize WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        
        // Download the image
        $temp_file = download_url($url);
        
        if (is_wp_error($temp_file)) {
            return false;
        }
        
        // Prepare file data
        $file_array = array(
            'name'     => sanitize_file_name($name),
            'tmp_name' => $temp_file,
        );
        
        // Move the file to the uploads directory
        $id = media_handle_sideload($file_array, 0);
        
        // Clean up temporary file
        @unlink($temp_file);
        
        if (is_wp_error($id)) {
            return false;
        }
        
        return $id;
    }

    /**
     * Sync inventory from ERP to WooCommerce
     */
    public function sync_inventory_from_erp() {
        $this->log('Starting inventory synchronization from ERP');
        
        // Get inventory data from ERP
        $erp_inventory = $this->erp_api_request('inventory', 'GET', array(
            'updated_since' => date('Y-m-d H:i:s', time() - $this->sync_interval),
            'limit' => 500
        ));
        
        if (is_wp_error($erp_inventory)) {
            $this->log('Failed to fetch inventory from ERP');
            return;
        }
        
        // Process inventory updates
        foreach ($erp_inventory as $item) {
            // Find product in WooCommerce by SKU
            $product_id = wc_get_product_id_by_sku($item['sku']);
            
            if ($product_id) {
                $product = wc_get_product($product_id);
                
                if ($product) {
                    // If it's a variation, update its stock
                    if ($product->is_type('variation')) {
                        $this->update_product_stock($product, $item['qty']);
                    } 
                    // If it's a variable product, find the variation
                    elseif ($product->is_type('variable')) {
                        // Check if SKU is for a variation
                        if (!empty($item['variation_attributes'])) {
                            // Try to find variation by attributes
                            $variation_id = $this->find_variation_by_attributes(
                                $product_id,
                                $item['variation_attributes']
                            );
                            
                            if ($variation_id) {
                                $variation = wc_get_product($variation_id);
                                $this->update_product_stock($variation, $item['qty']);
                            }
                        } else {
                            // Update stock of all variations
                            $variations = $product->get_children();
                            
                            foreach ($variations as $variation_id) {
                                $variation = wc_get_product($variation_id);
                                if ($variation && $variation->get_sku() == $item['sku']) {
                                    $this->update_product_stock($variation, $item['qty']);
                                    break;
                                }
                            }
                        }
                    } 
                    // Simple product
                    else {
                        $this->update_product_stock($product, $item['qty']);
                    }
                }
            }
        }
        
        $this->log('Completed inventory synchronization from ERP');
    }

    /**
     * Find variation by attributes
     *
     * @param int $product_id Parent product ID
     * @param array $attributes Variation attributes
     * @return int|false Variation ID or false if not found
     */
    private function find_variation_by_attributes($product_id, $attributes) {
        $data_store = WC_Data_Store::load('product');
        $variation_id = $data_store->find_matching_product_variation(
            wc_get_product($product_id),
            $attributes
        );
        
        return $variation_id;
    }

    /**
     * Update product stock
     *
     * @param WC_Product $product WooCommerce product
     * @param int $quantity New stock quantity
     */
    private function update_product_stock($product, $quantity) {
        if (!$product) {
            return;
        }
        
        // Get current stock
        $current_stock = $product->get_stock_quantity();
        
        // Only update if stock quantity has changed
        if ($current_stock !== $quantity) {
            $product->set_stock_quantity($quantity);
            $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
            $product->save();
            
            $this->log(sprintf(
                'Updated stock for product %s (ID: %d, SKU: %s) from %s to %s',
                $product->get_name(),
                $product->get_id(),
                $product->get_sku(),
                $current_stock,
                $quantity
            ));
        }
    }

    /**
     * Sync inventory to ERP when updated in WooCommerce
     *
     * @param WC_Product $product WooCommerce product
     */
    public function sync_inventory_to_erp($product) {
        if (!$product) {
            return;
        }
        
        // Skip if this is not a product variation and not a simple product
        if (!$product->is_type('variation') && !$product->is_type('simple')) {
            return;
        }
        
        $sku = $product->get_sku();
        
        if (empty($sku)) {
            return;
        }
        
        $stock_quantity = $product->get_stock_quantity();
        
        // Send inventory update to ERP
        $response = $this->erp_api_request('inventory', 'PUT', array(
            'sku' => $sku,
            'qty' => $stock_quantity
        ));
        
        if (is_wp_error($response)) {
            $this->log(sprintf(
                'Failed to update inventory in ERP for product %s (ID: %d, SKU: %s)',
                $product->get_name(),
                $product->get_id(),
                $sku
            ));
        } else {
            $this->log(sprintf(
                'Updated inventory in ERP for product %s (ID: %d, SKU: %s) to %s',
                $product->get_name(),
                $product->get_id(),
                $sku,
                $stock_quantity
            ));
        }
    }

    /**
     * Log a message
     *
     * @param string $message Message to log
     */
    private function log($message) {
        if ($this->log_enabled) {
            error_log('[ERP-WC Integration] ' . $message);
        }
    }
}

// Initialize the integration
new ERP_WooCommerce_Integration();