/**
 * Register the plugin settings page
 */
function erp_inventory_sync_register_settings_page() {
    add_menu_page(
        'ERP Inventory Sync',
        'ERP Sync',
        'manage_options',
        'erp-inventory-sync',
        'erp_inventory_sync_settings_page',
        'dashicons-update',
        30
    );
}
add_action('admin_menu', 'erp_inventory_sync_register_settings_page');


/**
 * Register plugin settings
 */
function erp_inventory_sync_register_settings() {
    // Register a settings group
    register_setting('erp_inventory_sync_settings', 'erp_inventory_sync_options');
    
    // WooCommerce to ERP settings
    add_settings_section(
        'woo_to_erp_section',
        '',
        '',
        'erp-inventory-sync'
    );
    
    // ERP to WooCommerce settings
    add_settings_section(
        'erp_to_woo_section',
        '',
        '',
        'erp-inventory-sync'
    );
    
    // Connection settings
    add_settings_section(
        'connection_section',
        '',
        '',
        'erp-inventory-sync'
    );
}
add_action('admin_init', 'erp_inventory_sync_register_settings');

/**
 * Enqueue admin scripts and styles
 */
function erp_inventory_sync_admin_scripts($hook) {
    if ('toplevel_page_erp-inventory-sync' !== $hook) {
        return;
    }
    
    wp_enqueue_style('erp-inventory-sync-admin', plugin_dir_url(__FILE__) . 'css/admin.css');
    wp_enqueue_script('erp-inventory-sync-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), '1.0.0', true);
}
add_action('admin_enqueue_scripts', 'erp_inventory_sync_admin_scripts');

/**
 * Render the settings page
 */
function erp_inventory_sync_settings_page() {
    // Get saved options
    $options = get_option('erp_inventory_sync_options', array());
    
    // Default values
    $defaults = array(
        'woo_to_erp' => array(
            'enabled' => false,
            'sync_orders' => false,
            'sync_refunds' => false,
            'sync_customers' => false
        ),
        'erp_to_woo' => array(
            'enabled' => false,
            'sync_products' => false,
            'sync_prices' => false,
            'sync_inventory' => false,
            'sync_images' => false
        ),
        'connection' => array(
            'api_key' => '',
            'api_endpoint' => '',
            'sync_frequency' => 'hourly'
        )
    );
    
    // Merge defaults with saved options
    $options = wp_parse_args($options, $defaults);
    ?>
    <div class="wrap">
        <h1>ERP Inventory Sync Settings</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('erp_inventory_sync_settings'); ?>
            
            <div class="erp-sync-accordion-container">
                <!-- WooCommerce to ERP Panel -->
                <div class="erp-sync-accordion">
                    <div class="erp-sync-accordion-header">
                        <h3>Push WooCommerce Data to ERP</h3>
                        <span class="toggle-indicator"></span>
                    </div>
                    <div class="erp-sync-accordion-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Sync</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="erp_inventory_sync_options[woo_to_erp][enabled]" 
                                               value="1" <?php checked(1, $options['woo_to_erp']['enabled']); ?> />
                                        Enable pushing data from WooCommerce to your ERP
                                    </label>
                                </td>
                            </tr>
                            <tr class="sub-option">
                                <th scope="row">Data to Sync</th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">Data to sync from WooCommerce to ERP</legend>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[woo_to_erp][sync_orders]" 
                                                   value="1" <?php checked(1, $options['woo_to_erp']['sync_orders']); ?> />
                                            Orders
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[woo_to_erp][sync_refunds]" 
                                                   value="1" <?php checked(1, $options['woo_to_erp']['sync_refunds']); ?> />
                                            Refunds
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[woo_to_erp][sync_customers]" 
                                                   value="1" <?php checked(1, $options['woo_to_erp']['sync_customers']); ?> />
                                            Customers
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- ERP to WooCommerce Panel -->
                <div class="erp-sync-accordion">
                    <div class="erp-sync-accordion-header">
                        <h3>Push ERP Data to WooCommerce</h3>
                        <span class="toggle-indicator"></span>
                    </div>
                    <div class="erp-sync-accordion-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Enable Sync</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="erp_inventory_sync_options[erp_to_woo][enabled]" 
                                               value="1" <?php checked(1, $options['erp_to_woo']['enabled']); ?> />
                                        Enable pushing data from your ERP to WooCommerce
                                    </label>
                                </td>
                            </tr>
                            <tr class="sub-option">
                                <th scope="row">Data to Sync</th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">Data to sync from ERP to WooCommerce</legend>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[erp_to_woo][sync_products]" 
                                                   value="1" <?php checked(1, $options['erp_to_woo']['sync_products']); ?> />
                                            Products
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[erp_to_woo][sync_prices]" 
                                                   value="1" <?php checked(1, $options['erp_to_woo']['sync_prices']); ?> />
                                            Prices
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[erp_to_woo][sync_inventory]" 
                                                   value="1" <?php checked(1, $options['erp_to_woo']['sync_inventory']); ?> />
                                            Inventory Levels
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="erp_inventory_sync_options[erp_to_woo][sync_images]" 
                                                   value="1" <?php checked(1, $options['erp_to_woo']['sync_images']); ?> />
                                            Product Images
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Connection Settings Panel -->
                <div class="erp-sync-accordion">
                    <div class="erp-sync-accordion-header">
                        <h3>Connection Settings</h3>
                        <span class="toggle-indicator"></span>
                    </div>
                    <div class="erp-sync-accordion-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row">ERP API Key</th>
                                <td>
                                    <input type="password" name="erp_inventory_sync_options[connection][api_key]" 
                                           value="<?php echo esc_attr($options['connection']['api_key']); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">ERP API Endpoint</th>
                                <td>
                                    <input type="url" name="erp_inventory_sync_options[connection][api_endpoint]" 
                                           value="<?php echo esc_url($options['connection']['api_endpoint']); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Sync Frequency</th>
                                <td>
                                    <select name="erp_inventory_sync_options[connection][sync_frequency]">
                                        <option value="hourly" <?php selected('hourly', $options['connection']['sync_frequency']); ?>>
                                            Hourly
                                        </option>
                                        <option value="twicedaily" <?php selected('twicedaily', $options['connection']['sync_frequency']); ?>>
                                            Twice Daily
                                        </option>
                                        <option value="daily" <?php selected('daily', $options['connection']['sync_frequency']); ?>>
                                            Daily
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Test Connection</th>
                                <td>
                                    <button type="button" id="test-erp-connection" class="button button-secondary">
                                        Test ERP Connection
                                    </button>
                                    <span id="connection-test-result" style="margin-left: 10px; display: inline-block;"></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
