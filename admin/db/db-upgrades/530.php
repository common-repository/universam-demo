<?php
global $wp_roles, $wpdb, $wp_rewrite;
	
delete_option( 'usam_order_document_number');
delete_option( 'usam_payment_document_number');	

USAM_Install::create_or_update_tables([USAM_TABLE_OBJECT_STATUSES]);


$wpdb->query("UPDATE ".USAM_TABLE_ORDERS." SET number=id");

$order_id = $wpdb->get_var("SELECT id FROM ".USAM_TABLE_ORDERS." ORDER BY id DESC");

$number_counter = get_option('usam_document_number_counter', []);
$number_counter['order'] = $order_id;
update_option("usam_document_number_counter", $number_counter);


$wpdb->query("DELETE FROM ".USAM_TABLE_OBJECT_STATUSES." WHERE type = 'company' OR type = 'contact' OR type = 'employee'");
$statuses = [	
	['internalname' => 'blocked', 'name' => __('Блокированный', 'usam'), 'short_name' => __('Блокированные', 'usam'), 'color' => '#d9d9d9', 'type' => 'contact'],	
	['internalname' => 'flagged', 'name' => __('Не перспективный', 'usam'), 'short_name' => __('Не перспективные', 'usam'), 'color' => '#ff9393', 'type' => 'contact'],	
	['internalname' => 'customer', 'name' => __('Нейтральный', 'usam'), 'short_name' => __('Нейтральные', 'usam'), 'color' => '#7db1c9', 'type' => 'contact'],
	['internalname' => 'prospect', 'name' => __('Перспективный', 'usam'), 'short_name' => __('Перспективные', 'usam'), 'color' => '#faeac0', 'type' => 'contact'],	
	['internalname' => 'favourite', 'name' => __('Любимый', 'usam'), 'short_name' => __('Любимые', 'usam'), 'color' => '#e6f0c0', 'type' => 'contact'],	

	['internalname' => 'blocked', 'name' => __('Блокированный', 'usam'), 'short_name' => __('Блокированные', 'usam'), 'color' => '#d9d9d9', 'type' => 'company'],	
	['internalname' => 'flagged', 'name' => __('Не перспективный', 'usam'), 'short_name' => __('Не перспективные', 'usam'), 'color' => '#ff9393', 'type' => 'company'],	
	['internalname' => 'customer', 'name' => __('Нейтральный', 'usam'), 'short_name' => __('Нейтральные', 'usam'), 'color' => '#7db1c9', 'type' => 'company'],	
	['internalname' => 'prospect', 'name' => __('Перспективный', 'usam'), 'short_name' => __('Перспективные', 'usam'), 'color' => '#faeac0', 'type' => 'company'],	
	['internalname' => 'favourite', 'name' => __('Любимый', 'usam'), 'short_name' => __('Любимые', 'usam'), 'color' => '#e6f0c0', 'type' => 'company'],		

	['internalname' => 'works', 'name' => __('Работает', 'usam'), 'short_name' => __('Работают', 'usam'), 'type' => 'employee', 'color' => '#e6f0c0'],	
	['internalname' => 'on_holiday', 'name' => __('В отпуске', 'usam'), 'short_name' => __('В отпуске', 'usam'), 'type' => 'employee', 'color' => '#7db1c9'],	
	['internalname' => 'hurt', 'name' => __('Болеет', 'usam'), 'short_name' => __('Болеют', 'usam'), 'type' => 'employee', 'color' => '#d9d9d9'],	
	['internalname' => 'output', 'name' => __('Выходной', 'usam'), 'short_name' => __('Выходной', 'usam'), 'type' => 'employee', 'color' => '#faeac0'],	
	['internalname' => 'business_trip', 'name' => __('В командировке', 'usam'), 'short_name' => __('В командировке', 'usam'), 'type' => 'employee', 'color' => '#ff9393'],
];	
foreach ( $statuses as $key => $status )
{ 
	$status['sort'] = $key+1;
	$status['active'] = isset($status['active'])?$status['active']:1;
	usam_insert_object_status( $status );
}

$capabilities = array( 
	'view_competitor_analysis' => array( 'administrator', 'shop_manager'),
	'view_procurement' => array( 'administrator', 'shop_manager'),
	'view_competitors_products' => array( 'administrator', 'shop_manager'),
	'view_price_analysis' => array( 'administrator', 'shop_manager'),
	'view_sets' => array( 'administrator', 'shop_manager'),
	'view_account_transactions' => array( 'administrator', 'shop_manager'),	
	'view_bookkeeping' => array( 'administrator', 'shop_manager'),	
	'view_bank_payments' => array( 'administrator', 'shop_manager'),
	'view_reconciliation_act' => array( 'administrator', 'shop_manager'),	
	'shop_tools' => array( 'administrator'),		
);	
foreach ( $capabilities as $capability_id => $roles ) 
{	
	foreach ( $wp_roles->role_objects as $wp_role ) 
	{				
		if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
			$wp_role->add_cap( $capability_id );
	}
}

