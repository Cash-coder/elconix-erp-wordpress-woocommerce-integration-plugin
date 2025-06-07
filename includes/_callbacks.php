<?php
// Display the admin options page (main page with options)
function erpsync_page_fn() {
	?>	
	<!-- if setting sync_mode != manual: hide "Sync Now" button -->
	<?php 
		$options = get_option('plugin_erpsync');
		// "manual" default if syncMode isn't set yet	
		// $sync_mode = isset($options['schedule_mode_wooToErp']) ? $options['schedule_mode_wooToErp'] : 'manual';
		// if ($sync_mode == 'auto') {
		// 	error_log('new sync mode is ' . $sync_mode . '. NOT rendering "Sincronizar Ahora" button');			
		// }
		$sync_mode_wooToErp = isset($options['schedule_mode_wooToErp']) ? $options['schedule_mode_wooToErp'] : 'manual';
		$sync_mode_erpToWoo = isset($options['schedule_mode_erpToWoo']) ? $options['schedule_mode_erpToWoo'] : 'manual';

		//manual sync is enabled in at least one direction. Used to show or hide "Sync Now" button
		$is_manual_sync_enabled = ($sync_mode_wooToErp === 'manual') || ($sync_mode_erpToWoo === 'manual');
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
			<?php if ($is_manual_sync_enabled) : ?>
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
      if($options['schedule_mode_wooToErp'] === 'manual'){
        
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

// Sync Mode field callback woo to ERP
function erpsync_syncmode_wooto_erp_fn() {
  $options = get_option('plugin_erpsync');
  $mode = isset($options['schedule_mode_wooToErp']) ? $options['schedule_mode_wooToErp'] : 'manual';
  ?>
  <select id="schedule_mode_wooToErp" name="plugin_erpsync[schedule_mode_wooToErp]">
      <option value="manual" <?php selected($mode, 'manual'); ?>>Manual</option>
      <option value="auto" <?php selected($mode, 'auto'); ?>>Automático</option>
  </select>
  <?php
}

// Time field callback wooToERP
function erpsync_scheduled_time_wooToErp_fn() {
  $options = get_option('plugin_erpsync');
  $time = isset($options['schedule_time_wooToErp']) ? $options['schedule_time_wooToErp'] : '12:00';
  ?>
  <input type="time" id="schedule_time_wooToErp" name="plugin_erpsync[schedule_time_wooToErp]" value="<?php echo esc_attr($time); ?>">
  <?php
}

// Sync Mode field callback ERP to woo 
function erpsync_syncmode_erpToWoo_fn() {
  $options = get_option('plugin_erpsync');
  $mode = isset($options['schedule_mode_erpToWoo']) ? $options['schedule_mode_erpToWoo'] : 'manual';
  ?>
  <select id="schedule_mode_erpToWoo" name="plugin_erpsync[schedule_mode_erpToWoo]">
      <option value="manual" <?php selected($mode, 'manual'); ?>>Manual</option>
      <option value="auto" <?php selected($mode, 'auto'); ?>>Automático</option>
  </select>
  <?php
}

// Time field callback erptoWoo
function erpsync_scheduled_time_erpToWoo_fn() {
  $options = get_option('plugin_erpsync');
  $time = isset($options['schedule_time_erpToWoo']) ? $options['schedule_time_erpToWoo'] : '12:00';
  ?>
  <input type="time" id="schedule_time_erpToWoo" name="plugin_erpsync[schedule_time_erpToWoo]" value="<?php echo esc_attr($time); ?>">
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
	if(isset($options['prods_sync']) && $options['prods_sync']) { 
    $checked = ' checked="checked" '; 
	}
	echo "<input ".$checked." id='returns_chk' name='plugin_erpsync[prods_sync]' type='checkbox' />";
}

function import_products_by_id_fn() {
	$options = get_option('plugin_erpsync');
	
	// Implement $value with proper fallback
	$value = isset($options['product_import_by_id']) ? esc_attr($options['product_import_by_id']) : '';
	
	// Output the textarea with inline CSS
	echo "<textarea 
					id='product_import_by_id_id' 
					name='plugin_erpsync[product_import_by_id]' 
					rows='4' 
					style='
							width: 60%;
							min-height: 100px;
							max-height: 200px;
							overflow-y: auto;
							padding: 8px;
							box-sizing: border-box;
							font-family: inherit;
							font-size: 14px;
					'>{$value}</textarea>";
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
	// echo "<input id='api_url' name='plugin_erpsync[text_string]' size='40' type='password' value='{$options['text_string']}' />";		
	$value = isset($options[$id]) ? $options[$id] : 'Introduzca la Clave de Licencia Plugin';
	echo "<input id='api_url_txtinput' name='plugin_erpsync[$id]' size='40' type='password' value='{$value}' />";
}

// API KEY
function setting_apikey_fn() {
	$options = get_option('plugin_erpsync');
	$value = isset($options['api_key']) ? $options['api_key'] : '';
	// echo "<input id='erpsync_api_key' name='plugin_erpsync['api_key']' size='40' type='password' value='{$value}' />";
	echo "<input id='erpsync_api_key' name='plugin_erpsync[api_key]' size='40' type='password' value='{$value}' />";
}

// wooToErp show/hide time field if sync mode is auto/manual
function time_schedule_wooToErp_input() {
  ?>
  <script>
  jQuery(document).ready(function($) {
      function toggleWooToErpTimeField() {
          var mode = $('#schedule_mode_wooToErp').val();
          if (mode === 'auto') {
              $('.schedule-time-field-wooToErp').show();
          } else {
              $('.schedule-time-field-wooToErp').hide();
          }
      }
      
      // Run on page load
      toggleWooToErpTimeField();
      
      // Run when select changes
      $('#schedule_mode_wooToErp').on('change', toggleWooToErpTimeField);
  });
  </script>
  <?php
}
add_action('admin_footer', 'time_schedule_wooToErp_input');  

// ErpToWoo show/hide time field if sync mode is auto/manual
function time_schedule_erpToWoo_input() {
  ?>
  <script>
  jQuery(document).ready(function($) {
      function toggleErpToWooTimeField() {
          var mode = $('#schedule_mode_erpToWoo').val();
          if (mode === 'auto') {
              $('.schedule-time-field-erpToWoo').show();
          } else {
              $('.schedule-time-field-erpToWoo').hide();
          }
      }
      
      // Run on page load
      toggleErpToWooTimeField();
      
      // Run when select changes
      $('#schedule_mode_erpToWoo').on('change', toggleErpToWooTimeField);
  });
  </script>
  <?php
}
add_action('admin_footer', 'time_schedule_erpToWoo_input');  

// show progress message to the user
function erp_sync_progress_handler() {
  ?>
  <script>
  jQuery(document).ready(function($) {
      // Show processing message
      // function showSyncProgress() {
      //     $('#erpsync-button')
			// 			.after('<div 
			// 				id="sync-progress" 
			// 				style="margin-top:10px; color:#0073aa;"
			// 				>🔄 Sincronización en progreso ...
			// 				</div>');
      // }

			function showSyncProgress() {
			$('#erpsync-button').after(`
				<div id="sync-progress" style="
					margin-top: 15px;
					padding: 15px;
					width: 30%;
					background: #f8f9f9;
					border-left: 4px solid #0073aa;
					border-radius: 3px;
					font-size: 15px;
					font-weight: 500;
					display: flex;
					align-items: center;
					gap: 12px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.1);
				">
					<svg width="24" height="24" viewBox="0 0 24 24" style="animation: spin 1s linear infinite;">
							<path fill="#0073aa" d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0020 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 004 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
					</svg>
					<span>Sincronización en progreso...</span>
				</div>
			`);
    
			// Add spin animation
			$('<style>')
				.prop('type', 'text/css')
				.html('@keyframes spin { 100% { transform: rotate(360deg); } }')
				.appendTo('head');
			}
      
      // Hide processing message
      function hideSyncProgress() {
          $('#sync-progress').remove();
          $('#erpsync-button').prop('disabled', false);
      }
      
      // Trigger on form submission, not button click
      $('#erpsync-button').closest('form').on('submit', function() {
          showSyncProgress();
          // $('#erpsync-button').prop('disabled', true);
      });
      
      // Make functions globally accessible for PHP triggers
      window.showSyncProgress = showSyncProgress;
      window.hideSyncProgress = hideSyncProgress;
  });
  </script>
  <?php
}
add_action('admin_footer', 'erp_sync_progress_handler');


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