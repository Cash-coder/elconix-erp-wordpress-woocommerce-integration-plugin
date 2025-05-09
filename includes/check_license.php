<?php
/**
 * Check license validity
 * 
 * @param string $license_code The license code to validate
 * @return bool True if license is valid, false otherwise
 */

// fix bug invalid license message not rendering
if (!session_id()) {
    session_start();
}

function check_license($license_code) {
    // API endpoint
    $api_url = 'https://api.codigo6.com/api/activate_license';
    
    // Get the client's IP address
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
    // request arguments
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
        ]
    ];
    
    // request and print response
    $response = wp_remote_post($api_url, $args);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('License API response: ' . print_r($response_body, true));
    
    // Check if response is valid and has status code 200
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
        if (isset($response_body['status']) && $response_body['status'] === true) {
            error_log('License is valid');
            // Clear any error flag
            $_SESSION['license_error'] = false;
            return true;
        } elseif (isset($response_body['status']) && $response_body['status'] === false) {
            error_log('License is INVALID');
            // Set the error flag
            $_SESSION['license_error'] = true;
            return false;
        }
    }
    
    // Default to error
    $_SESSION['license_error'] = true;
    return false;
}

// This function will run on every admin page
function display_license_error() {
    if (current_user_can('manage_options') && isset($_SESSION['license_error']) && $_SESSION['license_error'] === true) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Error:</strong> Clave de Licencia Inválida.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'display_license_error');