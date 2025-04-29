<?php
// ************************************************************************************************************

// Callback functions

function woo_to_erp_fn() {
	// echo '<p>Below are some examples of different option controls.</p>';
	$is_checked = isset($options[$args['label_for']]) ? 'checked' : '';
  echo '
  <label class="switch">
      <input type="checkbox" name="yourplugin_toggles[' . esc_attr($args['label_for']) . ']" ' . $is_checked . '>
      <span class="slider round"></span>
  </label>
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