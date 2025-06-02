<?php

require_once ERP_SYNC_PLUGIN_DIR . 'includes/user_notice.php';

class ImportById {
  public static function import(){
    $options = get_option('plugin_erpsync');

    $ids = $options['product_import_by_id'];
    
    //exit if no ids to import
    if (!$ids) {
      self::logger('There are no IDs to import');
      return ;
    }

    self::logger($ids);

    

  }

  private static function logger($message){
    UserNotice::log_message('[ImportById] ' . $message);
  }
}