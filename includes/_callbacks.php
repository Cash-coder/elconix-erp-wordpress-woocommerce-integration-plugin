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


// Display the admin options page (main page with options)
function erpsync_page_fn() {
	?>
	
	<!-- if setting sync_mode != manual: hide "Sync Now" button -->
	<?php 
		$options = get_option('plugin_erpsync');
		// "manual" default if syncMode isn't set yet	
		$sync_mode = isset($options['schedule_mode']) ? $options['schedule_mode'] : 'manual';
		if ($sync_mode == 'auto') {
			error_log('new sync mode is ' . $sync_mode . '. NOT rendering "Sincronizar Ahora" button');			
		}
	?>	
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h1>Integración Elconix ERP</h1>
			<!-- Some optional text here explaining the overall purpose of the options and what they relate to etc. -->
			<form action="options.php" method="post">
			<?php settings_fields('plugin_erpsync'); ?>
			<?php do_settings_sections('erp-sync'); ?>
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Guardar Cambios'); ?>" />
			</p>
			</form>
			<!-- show sync now button if manual sync mode is enabled -->
			<?php if ($sync_mode === 'manual') : ?>
				<form action="" method="post">
					<?php wp_nonce_field('erpsync_manual_sync', 'erpsync_nonce'); ?>
					<p>
						<input type="submit" name="erpsync_manual_sync" id="erpsync-button" class="button" value="Sincronizar Ahora" />
					</p>
				</form>
			<?php endif; ?>
		</div>
	<?php
}

// add manual sync button
add_action('admin_init', 'erpsync_handle_manual_sync');

// Handle the sync button submission
function erpsync_handle_manual_sync() {
  // Check if our form was submitted
  if (isset($_POST['erpsync_manual_sync'])) {
      // Verify the nonce for security
      if (!isset($_POST['erpsync_nonce']) || !wp_verify_nonce($_POST['erpsync_nonce'], 'erpsync_manual_sync')) {
          wp_die('Security check failed. Please try again.');
      }
      
      // Check user permissions
      if (!current_user_can('manage_options')) {
          wp_die('You do not have sufficient permissions to access this page.');
      }

      // show button only when manual mode is enabled
      $options = get_option('plugin_erpsync');
      if($options['schedule_mode'] === 'manual'){
        
				error_log('Detectado boton "Sincronizar Ahora". Modo manual activo. Procediendo con sincronizacion');
				$sync_result = perform_erp_sync();      
				
				// Set a transient to show a message after redirect
				set_transient('erpsync_message', $sync_result ? 'Sync successful!' : 'Sync failed!', 60);
	
				// Redirect to the same page to prevent form resubmission
				wp_redirect(add_query_arg('page', 'erp-sync', admin_url('options-general.php')));
				exit;
      };
  }
}

// text line displayed before the first option
function  section_text_fn() {
	echo '<p>Seleccione opciones de integración y haga click en <strong>Guardar Cambios</strong>.</p>';
}

// sync mode: manual or auto ********************************************

// Mode field callback
function erpsync_mode_fn() {
  $options = get_option('plugin_erpsync');
  $mode = isset($options['schedule_mode']) ? $options['schedule_mode'] : 'manual';
  ?>
  <select id="schedule_mode" name="plugin_erpsync[schedule_mode]">
      <option value="manual" <?php selected($mode, 'manual'); ?>>Manual</option>
      <option value="auto" <?php selected($mode, 'auto'); ?>>Automático</option>
  </select>
  <?php
}

// Time field callback
function erpsync_scheduled_time_fn() {
  $options = get_option('plugin_erpsync');
  $time = isset($options['schedule_time']) ? $options['schedule_time'] : '12:00';
  ?>
  <input type="time" id="schedule_time" name="plugin_erpsync[schedule_time]" value="<?php echo esc_attr($time); ?>">
  <?php
}

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
			// .sub-option {
			// 	margin-left: 2em;
			// }

			// tr.sub-option > th,
			// tr.sub-option > td {
			// 	padding-left: 2em;
				// border: 10px solid red;
			/
		</style>
	';
}

// API URL
function setting_api_url_fn() {
		$options = get_option('plugin_erpsync');
		// echo "<input id='api_url' name='plugin_erpsync[text_string]' size='40' type='text' value='{$options['text_string']}' />";
		
		// implement $value here to avoid error when the user hasnt saved the value yet, and therefore doesn't exist in the database yet
		$value = isset($options['api_url']) ? $options['api_url'] : '';
		echo "<input id='api_url_txtinput' name='plugin_erpsync[api_url]' size='40' type='text' value='{$options['api_url']}' />";
	}

// LICENCE KEY
function setting_license_key_fn() {
	$id = 'license_key';
	$options = get_option('plugin_erpsync');
	// echo "<input id='api_url' name='plugin_erpsync[text_string]' size='40' type='text' value='{$options['text_string']}' />";		
	$value = isset($options[$id]) ? $options[$id] : 'Introduzca sare	Clave de Licencia Plugin';
	echo "<input id='api_url_txtinput' name='plugin_erpsync[$id]' size='40' type='text' value='{$value}' />";
}

// API KEY
function setting_apikey_fn() {
	$options = get_option('plugin_erpsync');
	$value = isset($options['api_key']) ? $options['api_key'] : '';
	// echo "<input id='erpsync_api_key' name='plugin_erpsync['api_key']' size='40' type='password' value='{$value}' />";
	echo "<input id='erpsync_api_key' name='plugin_erpsync[api_key]' size='40' type='password' value='{$value}' />";
}

// SAMPLE CALLBACKS *************************

// TEXTAREA - Name: plugin_options[text_area]
// function setting_textarea_fn() {
// 	$options = get_option('plugin_erpsync');
// 	echo "<textarea id='erpsync_textarea_string' name='plugin_erpsync[text_area]' rows='7' cols='50' type='textarea'>{$options['text_area']}</textarea>";
// }

// PASSWORD-TEXTBOX - Name: plugin_erpsync[pass_string]
// function setting_pass_fn() {
// 	$options = get_option('plugin_erpsync');
// 	echo "<input id='erpsync_text_pass' name='plugin_erpsync[pass_string]' size='40' type='password' value='{$options['pass_string']}' />";
// }

// // CHECKBOX - Name: plugin_erpsync[chkbox2]
// function setting_chk2_fn() {
// 	$options = get_option('plugin_erpsync');
// 	if($options['chkbox2']) { $checked = ' checked="checked" '; }
// 	// if(isset($options['chkbox1']) && $options['chkbox1']) { 
//   //   $checked = ' checked="checked" '; 
//   // }
//   echo "<input ".$checked." id='erpsync_chk2' name='plugin_erpsync[chkbox2]' type='checkbox' />";
// }

// // RADIO-BUTTON - Name: plugin_erpsync[option_set1]
// function setting_radio_fn() {
// 	$options = get_option('plugin_erpsync');
// 	$items = array("Square", "Triangle", "Circle");
// 	foreach($items as $item) {
// 		$checked = ($options['option_set1']==$item) ? ' checked="checked" ' : '';
// 		echo "<label><input ".$checked." value='$item' name='plugin_erpsync[option_set1]' type='radio' /> $item</label><br />";
// 	}
// }

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

// // DROP-DOWN-BOX - Name: plugin_erpsync[dropdown1]
// function  erpsync_setting_dropdown_fn() {
// 	$options = get_option('plugin_erpsync');
// 	$items = array("Red", "Green", "Blue", "Orange", "White", "Violet", "Yellow");
// 	echo "<select id='drop_down1' name='plugin_erpsync[dropdown1]'>"; // dropdown1 holds the currently selected color.
// 	foreach($items as $item) {
//     // name='plugin_erpsync[dropdown1]' → Ensures the selected value is saved in the plugin_erpsync array under the key dropdown1.
// 		$selected = ($options['dropdown1']==$item) ? 'selected="selected"' : ''; // Mark as default choice: if saved value matches the current $item
// 		echo "<option value='$item' $selected>$item</option>";
// 	}
// 	echo "</select>";
// }