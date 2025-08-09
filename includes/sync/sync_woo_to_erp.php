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
        
        // Add some dummy processing time
        sleep(1);
        
        // Log completion
        UserNotice::log_message('[WooToErp] sync_woo_to_erp completed');
        
        return true;
    }
    
    /**
     * Additional dummy function for testing
     */
    public static function test_function() {
        UserNotice::log_message('[WooToErp] Test function called');
    }
}