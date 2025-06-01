<?php
/**
 * Check license validity
 * 
 * @param string $license_code The license code to validate
 * @return bool True if license is valid, false otherwise
 */

// fix bug invalid license message not rendering
// if (!session_id()) {
//     session_start();
// }

function check_license($license_code) {
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
        error_log("License check attempt {$attempt}/{$max_retries}");
        $response = wp_remote_post($api_url, $args);
        
        // Connection failed
        if (is_wp_error($response)) {
            error_log("Connection error, retrying...");
            if ($attempt < $max_retries) sleep(2);
            $attempt++;
            continue;
        }
        
        // Got API response
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Valid license
        if (isset($response_body['status']) && $response_body['status'] === true) {
            error_log('License is valid');
            // $_SESSION['license_error'] = false;
            return true;
        }
        // Invalid license
        elseif (isset($response_body['status']) && $response_body['status'] === false) {
            if ($attempt < $max_retries) {
                error_log("License invalid, retry attempt {$attempt}");
                sleep(2);
                $attempt++;
                continue;
            } else {
                error_log('License is INVALID (final attempt)');
                // $_SESSION['license_error'] = true;
                return false;
            }
        }
        
        // Invalid response format
        $attempt++;
    }
    
    error_log('License check failed after ' . $max_retries . ' attempts');
    // $_SESSION['license_error'] = true;
    return false;
}

// This function will run on every admin page
function display_license_error() {
    // if license_error true and page is erp-sync, show error message
    if (current_user_can('manage_options') && isset($_SESSION['license_error']) && $_SESSION['license_error'] === true && isset($_GET['page']) && $_GET['page'] === 'erp-sync') {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Error:</strong> Clave de Licencia Inválida.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'display_license_error');