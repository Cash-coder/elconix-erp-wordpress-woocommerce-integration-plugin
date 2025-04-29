<?php
// Sync function
function perform_erp_sync() {
  
  $options = get_option('erp_sync_toggles', []);

  error_log('running');
  sleep(5);
  return true;
}