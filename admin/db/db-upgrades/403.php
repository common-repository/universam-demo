<?php

global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_ORDERS)  );


$shop_company = get_option( 'usam_shop_company',0 ); 	
$wpdb->query("UPDATE `".USAM_TABLE_ORDERS."` SET bank_account_id=$shop_company");


$printing_form_options = get_option( 'usam_printing_form', array() );
if ( !empty($printing_form_options) )
{
	$new = array();
	foreach ( $printing_form_options as $key => $printing_form ) 
	{
		$new[$shop_company][$key] = $printing_form;
	}	
	update_option( 'usam_printing_form', $new );
}


$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_HISTORY."` DROP COLUMN `pay_up`" );


?>