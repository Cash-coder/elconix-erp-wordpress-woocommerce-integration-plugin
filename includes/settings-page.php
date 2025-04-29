<?php
// Register  settings. Add the settings section, and settings fields
function erpsync_init_fn(){
	
    register_setting(
        'plugin_erpsync', //group name, same as in settings_field()
        'plugin_erpsync', // variable name to store in DB
        'plugin_erpsync_validate' );

    add_settings_section(
        'main_section', // id
        'Configuración de sincronización ERP', // title
        'section_text_fn', // call back that displays the HTML
        'erp-sync'); // page, same as in add_settings_field() and do_settings_section()
      
    // woo to ERP
    add_settings_field(
        'woo_to_ERP',
        'Activar Woocommerce a ERP sync',
        'woo_to_erp_fn',
        'erp-sync',   // Your existing settings page slug
        'main_section',
        ['label_for' => 'woo_to_ERP'] // Target key in options array
    );

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

  add_settings_field(
    'plugin_chk1',
    'Restore Defaults Upon Reactivation?',
    'setting_chk1_fn',
    'erp-sync',
    'main_section');

    // ERP to woo
    // add_settings_field(
    //   'ERP_to_woo',
    //   // 'Sincronizar de Woocommerce a ERP',
    //   'Sincronizar de ERP a Woocommerce',
    //   'erp_sync_toggle_callback',
    //   'erp-sync',   // Your existing settings page slug
    //   'main_section',
    //   ['label_for' => 'ERP_to_woo'] // Target key in options array
//   );


  }

// Add sub page to the Settings Menu
function erpsync_add_page_fn() {
	add_options_page(
    'ERP Sync', // page title displayed in browser title bar    
    'ERP Sync', // display link in the settings menu
    'administrator', // access level
    'erp-sync', // unique page name
    'erpsync_page_fn'); // callback function to display the options form
}

// Validate user data for some/all of your input fields
function plugin_erpsync_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
	return $input; // return validated input
}
