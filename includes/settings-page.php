<?php
// Register  settings. Add the settings section, and settings fields
function erpsync_init_fn(){
  
  register_setting(
    'plugin_erpsync', //group name, same as in settings_field()
    'plugin_erpsync', // variable name to store in DB
    'plugin_erpsync_validate' // validation callback
  );

  // run scheduler handler function BEFORE submission of "Guardar Cambios" button
  add_action('update_option_plugin_erpsync', 'erpsync_scheduler_handler', 10, 3);

  add_settings_section(
    'main_section', // id
    'Configuración de Sincronización', // title
    'section_text_fn', // call back that displays the HTML
    'erp-sync' // page, same as in add_settings_field() and do_settings_section()
  ); 

  // woo to ERP  ********************************************
  add_settings_field(
    'woo_to_ERP',
    'Woocommerce a ERP',
    'woo_to_erp_fn',
    'erp-sync',   // Your existing settings page slug
    'main_section',
    ['label_for' => 'woo_to_ERP'] // Target key in options array
  );

  // sync mode: manual or auto for woo to ERP
  add_settings_field(
    'schedule_mode_wooToErp', // I'm confused, this seems to be the id key to retrieve from DB table wp_options
    'Modo Sincronización',
    'erpsync_syncmode_wooto_erp_fn',
    'erp-sync',
    'main_section',
    // 'sync_mode'
    [
      'label_for' => 'schedule_mode_wooToErp'
    ]
  ); 

  // manual time setting option for woo to ERP
  add_settings_field(
    'schedule_time_wooToErp',
    'Horario Sincronización',
    'erpsync_scheduled_time_wooToErp_fn',
    'erp-sync',
    'main_section',
    [
      'class' => 'schedule-time-field-wooToErp',
      'label_for' => 'schedule_time_wooToErp' 
    ]
  );

  // orders sync
  add_settings_field(
    'orders sync',
    'Sincronizar Pedidos',
    'orders_sync_fn',
    'erp-sync',
    'main_section',
    // 'orders_sync',
    [
      'label_for' => 'orders_sync',
      'class' => 'sub-option' // This will apply to the <tr>
    ]
  );
  
  // returns sync
  add_settings_field(
    'returns sync',
    'Sincronizar Devoluciones',
    'returns_sync_fn',
    'erp-sync',
    'main_section',
    'returns_sync'
  );

  // ERP to woo  ********************************************
  add_settings_field(
    'ERP_to_woo',
    'ERP a Woocommerce',
    'erp_to_woo_fn',
    'erp-sync',   // Your existing settings page slug
    'main_section',
    ['label_for' => 'erp_to_woo'] // Target key in options array
  );

  // sync mode: manual or auto for erp to woo
  add_settings_field(
    'schedule_mode_erpToWoo', // I'm confused, this seems to be the id key to retrieve from DB table wp_options
    'Modo Sincronización',
    'erpsync_syncmode_erpToWoo_fn',
    'erp-sync',
    'main_section',
    // 'sync_mode'
    [
      'label_for' => 'schedule_mode_erpToWoo'
    ]
  ); 
  
  // manual time setting option for erp to woo
  add_settings_field(
    'schedule_time_erpToWoo',
    'Horario Sincronización',
    'erpsync_scheduled_time_erpToWoo_fn',
    'erp-sync',
    'main_section',
    [
      'class' => 'schedule-time-field-erpToWoo',
      'label_for' => 'schedule_time_erpToWoo' 
    ]
  );

  //products sync
  add_settings_field(
    'prods sync',
    'Sincronizar Productos',
    'prods_sync_fn',
    'erp-sync',
    'main_section',
    'prods_sync' 
  );

  // License
  add_settings_field(
    'product_import_by_id', // id api_url
    'Importar productos por ID: (separados por comas)', // title
    'import_products_by_id_fn', // callback
    'erp-sync', // page
    'main_section', // section: same as id in add_settings_section()
    // 'license_key'
    // args array
  );

  // ERP API URL
  add_settings_field(
    'api url', // id api_url
    'API URL', // title
    'setting_api_url_fn', // callback
    'erp-sync', // page
    'main_section', // section: same as id in add_settings_section()
    'api_url'
    // args array
  );

  // ERP API key
  add_settings_field(
    'api_key',
    'API Key',
    'setting_apikey_fn',
    'erp-sync',
    'main_section',
    'api_key'
  );
  
  // add_settings_field(
  //   'plugin_text_pass',
  //   'Password Text Input',
  //   'setting_pass_fn',
  //   'erp-sync',
  //   'main_section'
  //   );
 
  // import prods by id
  add_settings_field(
    'license key', // id api_url
    'Clave de Licencia', // title
    'setting_license_key_fn', // callback
    'erp-sync', // page
    'main_section', // section: same as id in add_settings_section()
    'license_key'
    // args array
  );
 

  }



// Validate user data for some/all of your input fields
function plugin_erpsync_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	
  // $input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
	return $input; // return validated input
}



// SAMPLE FIELDS *********************
// add_settings_field('plugin_textarea_string', 'Large Textbox!', 'setting_textarea_fn', 'erp-sync', 'main_section');
// add_settings_field('plugin_chk2', 'A Checkbox', 'setting_chk2_fn', 'erp-sync', 'main_section');
// add_settings_field('radio_buttons', 'Select Shape', 'setting_radio_fn', 'erp-sync', 'main_section');
// add_settings_field('drop_down1', 'Select Color', 'erpsync_setting_dropdown_fn', 'erp-sync', 'main_section');
