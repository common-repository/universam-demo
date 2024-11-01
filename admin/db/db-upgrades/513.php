<?php
global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables([USAM_TABLE_SUBSCRIPTIONS,USAM_TABLE_DELIVERY_SERVICE,USAM_TABLE_SUBSCRIPTION_RENEWAL,USAM_TABLE_COMPANY_CONNECTIONS,USAM_TABLE_TRIGGERS,USAM_TABLE_TRIGGER_META]);
$wpdb->query("DELETE FROM `".USAM_TABLE_PAYMENT_HISTORY."` WHERE document_type!='order'"); 
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_HISTORY."` DROP COLUMN `document_type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_GATEWAY."` DROP COLUMN `cashbox`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_SHIPPED_DOCUMENTS."` DROP COLUMN `readiness`" );



$capabilities = array( 
	'sale' => array( 'administrator', 'shop_manager', 'shop_crm', 'company_management'),
	'view_automation' => array( 'administrator', 'shop_manager', 'shop_crm', 'company_management'),	
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}