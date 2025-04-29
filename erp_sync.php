<?php
/**
 * Plugin Name: ERP Sync
 * Description: Sync WooCommerce data with ERP system
 * Version: 1.0
 */

 /** to do
  * sampleoptions -> erpsync
  * plugin_options -> plugin_erpsync 
  * change some names (db, registers, ..), but keep it working
  *
  */


// Specify Hooks/Filters
register_activation_hook('erp-sync', 'add_erpsync_defaults_fn');
add_action('admin_init', 'erpsync_init_fn' );
add_action('admin_menu', 'erpsync_add_page_fn');

// Define default option settings
function add_erpsync_defaults_fn() {
	$tmp = get_option('plugin_erpsync'); // change for my options
    if(($tmp['chkbox1']=='on')||(!is_array($tmp))) {
		$arr = array(
      "dropdown1"=>"Orange",
      "text_area" => 
      "Space to put a lot of information here!",
      "text_string" => "Some sample text",
      "pass_string" => "123456",
      "chkbox1" => "", 
      "chkbox2" => "on", 
      "option_set1" => "Triangle");
		update_option('plugin_erpsync', $arr);
	}
}

// Register our settings. Add the settings section, and settings fields
function erpsync_init_fn(){
	
  register_setting(
    'plugin_erpsync', //group name, same as in settings_field()
    'plugin_erpsync', // variable name to store in DB
    'plugin_erpsync_validate' );
	
  add_settings_section(
    'main_section', // id
    'ERP Sync Settings', // title
    'section_text_fn', // call back that displays the HTML
    'erp-sync'); // page, same as in add_settings_field() and do_settings_section()
  
	add_settings_field(
    'plugin_text_string', // id
    'Text Input', // title
    'setting_string_fn', // callback
    'erp-sync', // page
    'main_section' // section: same as id in add_settings_section()
    // args array
  ); 
	
  add_settings_field('plugin_text_pass', 'Password Text Input', 'setting_pass_fn', 'erp-sync', 'main_section');
	add_settings_field('plugin_textarea_string', 'Large Textbox!', 'setting_textarea_fn', 'erp-sync', 'main_section');
	add_settings_field('plugin_chk2', 'A Checkbox', 'setting_chk2_fn', 'erp-sync', 'main_section');
	add_settings_field('radio_buttons', 'Select Shape', 'setting_radio_fn', 'erp-sync', 'main_section');
	add_settings_field('drop_down1', 'Select Color', 'erpsync_setting_dropdown_fn', 'erp-sync', 'main_section');
	add_settings_field('plugin_chk1', 'Restore Defaults Upon Reactivation?', 'setting_chk1_fn', 'erp-sync', 'main_section');
}

// Add sub page to the Settings Menu
function erpsync_add_page_fn() {
	add_options_page(
    'ERP Sync', // page title displayed in browser title bar    
    'ERP Sync', // display link in the settings menu
    'administrator', // access level
    'erp-sync', // unique page name
    'options_page_fn'); // callback function to display the options form
}

// ************************************************************************************************************

// Callback functions

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

// CHECKBOX - Name: plugin_erpsync[chkbox1]
function setting_chk1_fn() {
	$options = get_option('plugin_erpsync');
  $checked= '';
	// if($options['chkbox1']) { $checked = ' checked="checked" '; }
  if(isset($options['chkbox1']) && $options['chkbox1']) { 
    $checked = ' checked="checked" '; 
}
	echo "<input ".$checked." id='erpsync_chk1' name='plugin_erpsync[chkbox1]' type='checkbox' />";
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

// Display the admin options page
function options_page_fn() {
  ?>
    <div class="wrap">
      <div class="icon32" id="icon-options-general"><br></div>
      <h2>ERP Sync Options Page</h2>
      Some optional text here explaining the overall purpose of the options and what they relate to etc.
      <form action="options.php" method="post">
      <?php settings_fields('plugin_erpsync'); ?>
      <?php do_settings_sections('erp-sync'); ?>
      <p class="submit">
        <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
      </p>
      </form>
    </div>
  <?php
  }

// Validate user data for some/all of your input fields
function plugin_erpsync_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
	return $input; // return validated input
}

