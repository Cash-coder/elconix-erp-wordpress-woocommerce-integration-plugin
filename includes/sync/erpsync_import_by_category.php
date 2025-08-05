<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';
require_once ERP_SYNC_PLUGIN_DIR . 'includes/sync/sync_erp_to_woo.php';

class erpsync_import_by_category {

  public static function import_by_cat($options){
    
    $categories = $options['categories'];

    self::logger("Import By category triggered, categories to import are: \n" . $categories);
  }
  
  private static function logger($message){
    UserNotice::log_message('[ImportByCategory] ' . $message);
  }
}