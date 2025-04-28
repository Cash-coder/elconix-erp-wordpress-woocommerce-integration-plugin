<?php

// Register settings (add to existing admin_init hook or create one)
add_action('admin_init', 'yourplugin_register_toggle_settings');
function yourplugin_register_toggle_settings() {
    register_setting(
        'erp_sync_options_group', // Group name (use existing if you have one)
        'erp_sync_toggles',      // Option key (stores all toggles as an array)
        ['sanitize_callback' => 'sanitize_text_field'] // Basic sanitization
    );

    // Add a section (skip if you already have one)
    add_settings_section(
        'yourplugin_toggles_section',
        'Toggle Settings',
        '__return_empty_string', // No description needed
        'erp-sync'    // Use your existing settings page slug
    );

    // Add toggle fields
    add_settings_field(
        'enable_feature_x',
        'Enable Feature X',
        'yourplugin_toggle_callback',
        'erp-sync',   // Your existing settings page slug
        'yourplugin_toggles_section',
        ['label_for' => 'enable_feature_x'] // Target key in options array
    );
}


function yourplugin_toggle_callback($args) {
  $options = get_option('erp_sync_toggles', []);
  $is_checked = isset($options[$args['label_for']]) ? 'checked' : '';
  echo '
  <label class="switch">
      <input type="checkbox" name="yourplugin_toggles[' . esc_attr($args['label_for']) . ']" ' . $is_checked . '>
      <span class="slider round"></span>
  </label>
  ';
}

add_action('admin_head', 'yourplugin_toggle_styles');
function yourplugin_toggle_styles() {
    echo '
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-left: 10px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            transition: .2s;
            border-radius: 24px;
        }
        .slider:before {
            content: "";
            position: absolute;
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background: white;
            transition: .2s;
            border-radius: 50%;
        }
        input:checked + .slider { background: #2271b1; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
    ';
}