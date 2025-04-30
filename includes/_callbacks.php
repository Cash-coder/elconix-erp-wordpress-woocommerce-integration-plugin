<?php

// enqueue CSS
// add_action('admin_enqueue_scripts', 'erp_sync_admin_styles');

// function erp_sync_admin_styles($hook) {
    // Only load on your plugin's settings page
    // error_log('aaa');
    // error_log($hook);
		// if ('settings_page_erp-sync' === $hook) {
        // wp_enqueue_style(
        //     'erp-sync-admin-css',
        //     plugins_url('css/admin.css', __FILE__),
        //     array(),
            // filetime(plugin_dir_path(__FILE__) . 'css/admin.css')
        // );
    // }
// }

// Woo to ERP *********************************************
function woo_to_erp_fn() {
	$options = get_option('plugin_erpsync');
	$checked = isset($options['woo_to_ERP']) ? 'checked' : '';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="woo_to_ERP" name="plugin_erpsync[woo_to_ERP]" value="1" ' . $checked . '>';
	echo '<span class="slider round"></span>';
	echo '</label>';
}

// orders
function orders_sync_fn() {
	$options = get_option('plugin_erpsync');
	$checked = isset($options['orders_sync']) ? 'checked' : '';
	// $checked= '';
	if(isset($options['orders_sync']) && $options['orders_sync']) { 
    $checked = ' checked="checked" '; 
	}
	// echo "<input class='sub-option' ".$checked." id='orders_chk' name='plugin_erpsync[orders_sync]' type='checkbox' />";
	echo '<div class="sub-option"><input id="orders_chk" name="plugin_erpsync[orders_sync]" type="checkbox" /></div>';
}

// returns
function returns_sync_fn() {
	$options = get_option('plugin_erpsync');
	$checked = isset($options['returns_sync']) ? 'checked' : '';
	// $checked= '';
	if(isset($options['returns_sync']) && $options['returns_sync']) { 
    $checked = ' checked="checked" '; 
	}
	echo "<input ".$checked." id='returns_chk' name='plugin_erpsync[returns_sync]' type='checkbox' />";
}

// ERP to woo *********************************************
function erp_to_woo_fn() {
	$options = get_option('plugin_erpsync');
	$checked = isset($options['erp_to_woo']) ? 'checked' : '';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="erp_to_woo" name="plugin_erpsync[erp_to_woo]" value="1" ' . $checked . '>';
	echo '<span class="slider round"></span>';
	echo '</label>';
}

// products
function prods_sync_fn() {
	$options = get_option('plugin_erpsync');
	$checked = isset($options['prods_sync']) ? 'checked' : '';
	// $checked= '';
	if(isset($options['prods_sync']) && $options['prods_sync']) { 
    $checked = ' checked="checked" '; 
	}
	echo "<input ".$checked." id='returns_chk' name='plugin_erpsync[prods_sync]' type='checkbox' />";
}

// CSS
// Woo to ERP
add_action('admin_head', 'erp_sync_toggle_styles');
function erp_sync_toggle_styles() {
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

add_action('admin_head', 'erp_sync_suboptions_styles');
function erp_sync_suboptions_styles() {
	echo '
		<style>
			.sub-option {
				margin-left: 2em;
			}

			// tr.sub-option > th,
			// tr.sub-option > td {
			// 	padding-left: 2em;
				// border: 10px solid red;
			/
		</style>
	';
}

// Section HTML, displayed before the first option
function  section_text_fn() {
	echo '<p>Below are some examples of different option controls.</p>';
}

// DROP-DOWN-BOX - Name: plugin_erpsync[dropdown1]
function  erpsync_setting_dropdown_fn() {
	$options = get_option('plugin_erpsync');
	$items = array("Red", "Green", "Blue", "Orange", "White", "Violet", "Yellow");
	echo "<select id='drop_down1' name='plugin_erpsync[dropdown1]'>"; // dropdown1 holds the currently selected color.
	foreach($items as $item) {
    // name='plugin_erpsync[dropdown1]' → Ensures the selected value is saved in the plugin_erpsync array under the key dropdown1.
		$selected = ($options['dropdown1']==$item) ? 'selected="selected"' : ''; // Mark as default choice: if saved value matches the current $item
		echo "<option value='$item' $selected>$item</option>";
	}
	echo "</select>";
}

// TEXTAREA - Name: plugin_options[text_area]
function setting_textarea_fn() {
	$options = get_option('plugin_erpsync');
	echo "<textarea id='erpsync_textarea_string' name='plugin_erpsync[text_area]' rows='7' cols='50' type='textarea'>{$options['text_area']}</textarea>";
}

// TEXTBOX - Name: plugin_erpsync[text_string]
function setting_string_fn() {
	$options = get_option('plugin_erpsync');
	echo "<input id='erpsync_text_string' name='plugin_erpsync[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}

// PASSWORD-TEXTBOX - Name: plugin_erpsync[pass_string]
function setting_pass_fn() {
	$options = get_option('plugin_erpsync');
	echo "<input id='erpsync_text_pass' name='plugin_erpsync[pass_string]' size='40' type='password' value='{$options['pass_string']}' />";
}

// CHECKBOX - Name: plugin_erpsync[chkbox2]
function setting_chk2_fn() {
	$options = get_option('plugin_erpsync');
	if($options['chkbox2']) { $checked = ' checked="checked" '; }
	// if(isset($options['chkbox1']) && $options['chkbox1']) { 
  //   $checked = ' checked="checked" '; 
  // }
  echo "<input ".$checked." id='erpsync_chk2' name='plugin_erpsync[chkbox2]' type='checkbox' />";
}

// RADIO-BUTTON - Name: plugin_erpsync[option_set1]
function setting_radio_fn() {
	$options = get_option('plugin_erpsync');
	$items = array("Square", "Triangle", "Circle");
	foreach($items as $item) {
		$checked = ($options['option_set1']==$item) ? ' checked="checked" ' : '';
		echo "<label><input ".$checked." value='$item' name='plugin_erpsync[option_set1]' type='radio' /> $item</label><br />";
	}
}


// // CHECKBOX - Name: plugin_erpsync[chkbox1]
// function setting_chk1_fn() {
// 	$options = get_option('plugin_erpsync');
//   $checked= '';
// 	// if($options['chkbox1']) { $checked = ' checked="checked" '; }
//   if(isset($options['chkbox1']) && $options['chkbox1']) { 
//     $checked = ' checked="checked" '; 
// 	}
// 	echo "<input ".$checked." id='erpsync_chk1' name='plugin_erpsync[chkbox1]' type='checkbox' />";
// }
