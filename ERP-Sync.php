<?php
/**
 * Plugin Name: ERP Sync
 * Description: Sync WooCommerce data with ERP system
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

class Woo_ERP_Sync {

    public function __construct() {
        // Initialize plugin
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        
        // Sync actions
        add_action('wp_ajax_woo_erp_manual_sync', [$this, 'manual_sync']);
        add_action('woo_erp_daily_sync', [$this, 'daily_sync']);
        
        // WooCommerce hooks
        add_action('woocommerce_new_order', [$this, 'maybe_sync_new_order']);
    }

    // Admin interface
    public function add_admin_page() {
        add_submenu_page(
            'woocommerce',
            'ERP Sync',
            'ERP Sync',
            'manage_options',
            'woo-erp-sync',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('woo_erp_sync_settings', 'woo_erp_options');
        
        add_settings_section(
            'sync_settings',
            'Sync Configuration',
            null,
            'woo-erp-sync'
        );

        add_settings_field(
            'sync_direction',
            'Sync Direction',
            [$this, 'sync_direction_callback'],
            'woo-erp-sync',
            'sync_settings'
        );

        add_settings_field(
            'sync_mode',
            'Sync Mode',
            [$this, 'sync_mode_callback'],
            'woo-erp-sync',
            'sync_settings'
        );
    }

    public function sync_direction_callback() {
        $options = get_option('woo_erp_options');
        ?>
        <label>
            <input type="checkbox" name="woo_erp_options[woo_to_erp]" value="1" <?php checked(1, $options['woo_to_erp'] ?? 0); ?>>
            WooCommerce → ERP (Orders, Customers)
        </label><br>
        <label>
            <input type="checkbox" name="woo_erp_options[erp_to_woo]" value="1" <?php checked(1, $options['erp_to_woo'] ?? 0); ?>>
            ERP → WooCommerce (Inventory, Products)
        </label>
        <?php
    }

    public function sync_mode_callback() {
        $options = get_option('woo_erp_options');
        ?>
        <select name="woo_erp_options[sync_mode]">
            <option value="auto" <?php selected($options['sync_mode'] ?? '', 'auto'); ?>>Automatic (Daily)</option>
            <option value="manual" <?php selected($options['sync_mode'] ?? '', 'manual'); ?>>Manual Only</option>
        </select>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>WooCommerce ERP Sync</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('woo_erp_sync_settings');
                do_settings_sections('woo-erp-sync');
                submit_button();
                ?>
            </form>
            
            <div class="sync-actions">
                <h2>Manual Sync</h2>
                <button id="erp-sync-now" class="button button-primary">Sync Now</button>
                <span class="spinner"></span>
                <div id="sync-results"></div>
            </div>
        </div>
        <?php
    }

    public function admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_woo-erp-sync') return;
        
        wp_enqueue_script(
            'woo-erp-admin',
            plugin_dir_url(__FILE__) . 'admin.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'admin.js'),
            true
        );
        
        wp_localize_script('woo-erp-admin', 'woo_erp_vars', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('woo_erp_sync_nonce')
        ]);
    }

    // Sync functionality
    public function manual_sync() {
        check_ajax_referer('woo_erp_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $results = [];
        $options = get_option('woo_erp_options');
        
        // WooCommerce to ERP sync
        if (!empty($options['woo_to_erp'])) {
            $results['woo_to_erp'] = $this->sync_woo_to_erp();
        }
        
        // ERP to WooCommerce sync
        if (!empty($options['erp_to_woo'])) {
            $results['erp_to_woo'] = $this->sync_erp_to_woo();
        }

        wp_send_json_success($results);
    }

    public function daily_sync() {
        $options = get_option('woo_erp_options');
        
        if ($options['sync_mode'] !== 'auto') return;
        
        if (!empty($options['woo_to_erp'])) {
            $this->sync_woo_to_erp();
        }
        
        if (!empty($options['erp_to_woo'])) {
            $this->sync_erp_to_woo();
        }
    }

    public function maybe_sync_new_order($order_id) {
        $options = get_option('woo_erp_options');
        
        if (!empty($options['woo_to_erp']) && $options['sync_mode'] === 'auto') {
            $this->push_order_to_erp($order_id);
        }
    }

    // Core sync methods
    private function sync_woo_to_erp() {
        // Implement pushing WooCommerce data to ERP
        return ['status' => 'success', 'message' => 'WooCommerce to ERP sync completed'];
    }

    private function sync_erp_to_woo() {
        // Implement pulling ERP data to WooCommerce
        return ['status' => 'success', 'message' => 'ERP to WooCommerce sync completed'];
    }

    private function push_order_to_erp($order_id) {
        // Implement single order push to ERP
        return true;
    }

    // Activation/deactivation
    public static function activate() {
        if (!wp_next_scheduled('woo_erp_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'woo_erp_daily_sync');
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('woo_erp_daily_sync');
    }
}

// Initialize plugin
new Woo_ERP_Sync();

// Register hooks
register_activation_hook(__FILE__, ['Woo_ERP_Sync', 'activate']);
register_deactivation_hook(__FILE__, ['Woo_ERP_Sync', 'deactivate']);