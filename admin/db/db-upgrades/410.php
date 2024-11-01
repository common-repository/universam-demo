<?php
global $wpdb;

USAM_Install::create_or_update_tables( array(USAM_TABLE_GROUPS, USAM_TABLE_ORDERS, USAM_TABLE_EXCHANGE_RULES, USAM_TABLE_EXCHANGE_RULE_META) );

delete_option('usam_departments');
$wpdb->query( "DROP TABLE {$wpdb->prefix}usam_order_props_value" );

$orders = $wpdb->get_results( "SELECT * FROM ".USAM_TABLE_ORDERS." WHERE cancellation_reason!='' AND cancellation_reason!='0.00000' AND cancellation_reason!='0.000000'" );	
foreach ( $orders as $order )
	usam_update_order_metadata( $order->id, 'cancellation_reason', $order->cancellation_reason );	

$wpdb->query( "ALTER TABLE `".USAM_TABLE_ORDERS."` DROP COLUMN `cancellation_reason`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_FILTERS."` DROP COLUMN `page`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_FILTERS."` DROP COLUMN `table`" );


$wpdb->query("DELETE FROM `".USAM_TABLE_COMPANY_META."` WHERE meta_key='company_email'"); 
$wpdb->query("DELETE FROM `".USAM_TABLE_COMPANY_META."` WHERE meta_key='company_phone'"); 


$option = get_option('usam_crm_company_group');
$contact_statuses = maybe_unserialize( $option );	
$ids = array();
foreach( $contact_statuses as $key => $item )
{	
	$item['type'] = 'company';
	$ids[$item['id']] = usam_insert_group( $item );
}

$companies = usam_get_companies(  );
foreach( $companies as $company )
{
	if ( $company->group && !empty($ids[$company->group]) )
		usam_set_groups_object( $company->id, 'company', array( $ids[$company->group] ) );
}

require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );

$option = get_option("usam_product_importer_rules");	
$rules = maybe_unserialize( $option );				
if ( !empty($rules) )
	foreach( $rules as $role )
	{			
		$role['type'] = 'product_import';
		$id = usam_insert_exchange_rule( $role );	
		unset($role['name']);
		unset($role['type']);
		unset($role['id']);
		unset($role['type_file']);
		unset($role['orderby']);
		unset($role['order']);
		if ( isset($role['headings']) )
			unset($role['headings']);
		foreach ( $role as $key => $value )
		{
			usam_update_exchange_rule_metadata( $id, $key, $value );	
		}	
	}	
$option = get_option("usam_product_exporter_rules");	
$rules = maybe_unserialize( $option );				
if ( !empty($rules) )
	foreach( $rules as $role )
	{			
		$role['type'] = 'product_export';
		$id = usam_insert_exchange_rule( $role );	
		unset($role['name']);
		unset($role['type']);
		unset($role['id']);
		unset($role['type_file']);
		unset($role['orderby']);
		unset($role['order']);
		if ( isset($role['headings']) )
			unset($role['headings']);
		foreach ( $role as $key => $value )
		{
			usam_update_exchange_rule_metadata( $id, $key, $value );	
		}		
	}	
$option = get_option("usam_orders_export_rules");	
$rules = maybe_unserialize( $option );				
if ( !empty($rules) )
	foreach( $rules as $role )
	{			
		$role['type'] = 'order_export';
		$id = usam_insert_exchange_rule( $role );	
		unset($role['name']);
		unset($role['type']);
		unset($role['id']);
		unset($role['type_file']);
		unset($role['orderby']);
		unset($role['order']);
		if ( isset($role['headings']) )
			unset($role['headings']);
		foreach ( $role as $key => $value )
		{
			usam_update_exchange_rule_metadata( $id, $key, $value );	
		}		
	}	
$option = get_option("usam_contacts_export_rules");	
$rules = maybe_unserialize( $option );				
if ( !empty($rules) )
	foreach( $rules as $role )
	{			
		$role['type'] = 'contact_export';
		$id = usam_insert_exchange_rule( $role );	
		unset($role['name']);
		unset($role['type']);
		unset($role['id']);
		unset($role['type_file']);
		unset($role['orderby']);
		unset($role['order']);
		if ( isset($role['headings']) )
			unset($role['headings']);
		foreach ( $role as $key => $value )
		{
			usam_update_exchange_rule_metadata( $id, $key, $value );	
		}		
	}	
	
$option = get_option("usam_companies_export_rules");	
$rules = maybe_unserialize( $option );				
if ( !empty($rules) )
	foreach( $rules as $role )
	{			
		$role['type'] = 'company_export';
		$id = usam_insert_exchange_rule( $role );	
		unset($role['name']);
		unset($role['type']);
		unset($role['id']);
		unset($role['type_file']);
		unset($role['orderby']);
		unset($role['order']);
		if ( isset($role['headings']) )
			unset($role['headings']);
		foreach ( $role as $key => $value )
		{
			usam_update_exchange_rule_metadata( $id, $key, $value );	
		}		
	}	
?>