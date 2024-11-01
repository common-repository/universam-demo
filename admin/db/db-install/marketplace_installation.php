<?php
require_once(USAM_FILE_PATH.'/includes/installer.class.php');
USAM_Install::create_or_update_tables( );	
require_once( USAM_FILE_PATH.'/includes/customer/capabilities_schema.php' );	

require_once( USAM_FILE_PATH . '/admin/db/db-install/system_default_data.php' );	
new USAM_Load_System_Default_Data(['marketplace_product_attributes']);

global $wp_rewrite;
$wp_rewrite->flush_rules();