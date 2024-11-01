<?php
global $wp_roles, $wpdb, $wp_rewrite;
		

USAM_Install::create_or_update_tables([USAM_TABLE_VISITS, USAM_TABLE_CONTACTS, USAM_TABLE_COMPANY, USAM_TABLE_SYSTEM_REPORTS, USAM_TABLE_OBJECT_STATUSES]);

$wpdb->query("UPDATE ".USAM_TABLE_ORDERS." SET number=id");

$order_id = $wpdb->get_var("SELECT id FROM ".USAM_TABLE_ORDERS." ORDER BY id DESC");

$number_counter = get_option('usam_document_number_counter', []);
$number_counter['order'] = $order_id;
$number_counter['payment'] = get_option('usam_payment_document_number', 'PH00000000001');
update_option("usam_document_number_counter", $number_counter);
delete_option( 'usam_order_document_number');
delete_option( 'usam_payment_document_number');

$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET type='task' WHERE `type`='assigned_task'");
$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET type='task' WHERE `type`='event'");
$statuses = [
	['internalname' => 'not_started', 'name' => __('Запланирован', 'usam'), 'short_name' => __('Запланированы', 'usam'), 'active' => 1, 'close'  => false, 'type' => 'project'],
	['internalname' => 'started', 'name' => __('В работе', 'usam'), 'short_name' => __('В работе', 'usam'), 'active' => 1, 'close'  => false, 'type' => 'project'],
	['internalname' => 'completed', 'name' => __('Завершен', 'usam'), 'short_name' => __('Завершены', 'usam'), 'active' => 1, 'close'  => false, 'type' => 'project'],
	['internalname' => 'canceled', 'name' => __('Отменен', 'usam'), 'short_name' => __('Отмененные', 'usam'), 'active' => 1, 'close'  => false, 'type' => 'project'],
];	
foreach ( $statuses as $key => $status )
{ 
	$status['sort'] = $key+1;
	$status['active'] = isset($status['active'])?$status['active']:1;
	usam_insert_object_status( $status );
}


global $wp_roles, $wpdb, $wp_rewrite;
$orders = $wpdb->get_results( "SELECT contact_id, date_insert  FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND contact_id!=0 GROUP BY contact_id" );	
foreach ( $orders as $order )
{	
	$orders = $wpdb->get_row("SELECT COUNT(*) AS number_orders, SUM(totalprice) AS total_purchased FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND contact_id=".$order->contact_id."" );			
	usam_update_contact($order->contact_id, ['total_purchased' => $orders->total_purchased, 'number_orders' => $orders->number_orders, 'last_order_date' => $order->date_insert]);			
}	

$orders = $wpdb->get_results( "SELECT company_id, date_insert FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND company_id!=0 GROUP BY company_id" );	
foreach ( $orders as $order )
{
	$orders = $wpdb->get_row("SELECT COUNT(*) AS number_orders, SUM(totalprice) AS total_purchased FROM ".USAM_TABLE_ORDERS." WHERE status='closed' AND company_id='".$order->company_id."'" );
	usam_update_company($order->company_id, ['total_purchased' => $orders->total_purchased, 'number_orders' => $orders->number_orders, 'last_order_date' => $order->date_insert]);
}
		


