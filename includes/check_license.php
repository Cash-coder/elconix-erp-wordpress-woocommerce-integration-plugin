<?php
/**
 * Check license validity
 * 
 * @param string $license_code The license code to validate
 * @return bool True if license is valid, false otherwise
 */

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class License {

    public static function check_license($license_code) {
        $api_url = 'https://api.codigo6.com/api/activate_license';
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $max_retries = 3;
        $attempt = 1;
        
        $args = [
            'body' => json_encode([
                'verify_type' => 'non_envato',
                'product_id' => 'A220135B',
                'license_code' => $license_code,
                'client_name' => '-'
            ]),
            'headers' => [
                'LB-API-KEY' => '7C2BEEA1921AFBCC172EwhatsappC6',
                'LB-URL' => site_url(),
                'LB-IP' => $client_ip,
                'LB-LANG' => 'english',
                'Content-Type' => 'application/json'
            ],
            'timeout' => 15
        ];
        
        while ($attempt <= $max_retries) {
            self::logger("License check attempt {$attempt}/{$max_retries}");
            $response = wp_remote_post($api_url, $args);
            
            // Connection failed
            if (is_wp_error($response)) {
                self::logger("Connection error, retrying...");
                if ($attempt < $max_retries) sleep(2);
                $attempt++;
                continue;
            }
            
            // Got API response
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            // Valid license
            if (isset($response_body['status']) && $response_body['status'] === true) {
                self::logger('License is valid');
                // $_SESSION['license_error'] = false;
                return true;
            }
            // Invalid license
            elseif (isset($response_body['status']) && $response_body['status'] === false) {
                if ($attempt < $max_retries) {
                    self::logger("License invalid, retry attempt {$attempt}");
                    sleep(2);
                    $attempt++;
                    continue;
                } else {
                    self::logger('License is INVALID (final attempt)');
                    // $_SESSION['license_error'] = true;
                    return false;
                }
            }
            
            // Invalid response format
            $attempt++;
        }
        
        self::logger('License check failed after ' . $max_retries . ' attempts');
        // $_SESSION['license_error'] = true;
        return false;
    }

    private static function logger($message){
        UserNotice::log_message('[License] ' . $message);
    }

}

