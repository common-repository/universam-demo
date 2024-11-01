<?php
global $wpdb;

delete_option('usam_record_affairs_managers');


USAM_Install::create_or_update_tables( array(USAM_TABLE_CHANGE_HISTORY,USAM_TABLE_EVENTS, USAM_TABLE_BONUS_CARDS, USAM_TABLE_BONUS_TRANSACTIONS, USAM_TABLE_PRODUCTS_BASKET )  );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_DOCUMENT_PRODUCTS."` DROP COLUMN `tax`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_ORDERS."` DROP COLUMN `bonus`" );

$statuses = array( 0 => __('Не начата', 'usam'), 1 => __('Выполняется', 'usam'),  2 => __('Остановлена', 'usam') , 3 => __('Завершена', 'usam'), 4 => __('Отменена', 'usam') );	

$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET status='not_started' WHERE `status`='0'");
$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET status='started' WHERE `status`='1'");
$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET status='stopped' WHERE `status`='2'");
$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET status='completed' WHERE `status`='3'");
$wpdb->query("UPDATE ".USAM_TABLE_EVENTS." SET status='canceled' WHERE `status`='4'");

require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
$bonuses = usam_get_bonuses();
foreach ( $bonuses as $bonus )
{
	if ( $bonus->user_id == 0 )
	{
		usam_delete_bonus( $bonus->id );
		continue;
	}
	$description = usam_get_bonus_type($bonus->type);
	if ( $bonus->status == 'locked_time' || $bonus->status == 'available')
		$type_transaction = 0;
	elseif ( $bonus->status == 'blocked_after_use' || $bonus->status == 'used')	
		$type_transaction = 1;
	else
	{
		usam_delete_bonus( $bonus->id );
		continue;
	}
	$bonus_card = usam_get_bonus_card( $bonus->user_id, 'user_id' ); 		
	if ( empty($bonus_card['code']) )
	{
		$code = usam_insert_bonus_card( array('code' => '100780'.$bonus->id, 'user_id' => $bonus->user_id, 'status' => 'active' ) );	
	}
	else
		$code =  $bonus_card['code'];
	
	usam_update_bonus( $bonus->id, array('description' => $description, 'sum' => $bonus->bonus, 'type_transaction' => $type_transaction, 'code' => $code ) );
	if ( $type_transaction )
	{		
		usam_insert_bonus( array('description' => $description, 'order' => $bonus->payment_order_id, 'sum' => $bonus->bonus, 'type_transaction' => 0, 'code' => $code ) );
	}
}
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `date_update`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `use_date`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `payment_order_id`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `bonus`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `status`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `type`" );
$wpdb->query( "ALTER TABLE `".USAM_TABLE_BONUS_TRANSACTIONS."` DROP COLUMN `user_id`" );


global $wpdb;
$contact_ids = array();
$company_ids = array();


$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usam_means_communication_list"   );	
foreach ( $results as $key => $result )
{
	if ( $result->customer_type == 'contact' )
	{
		$r = $wpdb->get_var( "SELECT id FROM " . USAM_TABLE_CONTACTS." WHERE id=$result->contact_id"  );	
		if ( $r )
		{
			if ( empty($contact_ids[$result->contact_id]) )
				$contact_ids[$result->contact_id] = array( 'email' => 0, 'phone' => 0);
			if ( $result->type == 'phone' )
			{			
				$contact_ids[$result->contact_id]['phone']++;
				if ( $contact_ids[$result->contact_id]['phone'] == 1 )
					usam_update_contact_metadata($result->contact_id, 'mobilephone', $result->value  );			
				elseif ( $contact_ids[$result->contact_id]['phone'] == 2 )	
					usam_update_contact_metadata($result->contact_id, 'mobilephone2', $result->value  );
				else
					usam_update_contact_metadata($result->contact_id, 'phone', $result->value  );
			}
			elseif ( $result->type == 'email' )
			{
				$contact_ids[$result->contact_id]['email']++;
				if ( $contact_ids[$result->contact_id]['email'] == 1 )
					usam_update_contact_metadata($result->contact_id, 'email', $result->value  );
				else
					usam_update_contact_metadata($result->contact_id, 'workemail', $result->value  );			
				
			}
		}
	}
	elseif ( $result->customer_type == 'company' )
	{
		$r = $wpdb->get_var( "SELECT id FROM " . USAM_TABLE_COMPANY." WHERE id=$result->contact_id"  );	
		if ( $r )
		{
			if ( empty($company_ids[$result->contact_id]) )
				$company_ids[$result->contact_id] = array( 'email' => 0, 'phone' => 0);
			if ( $result->type == 'phone' )
			{			
				$company_ids[$result->contact_id]['phone']++;
				if ( $company_ids[$result->contact_id]['phone'] == 1 )
					usam_update_company_metadata($result->contact_id, 'mobilephone', $result->value  );
				else
					usam_update_company_metadata($result->contact_id, 'mobilephone2', $result->value  );
			}
			elseif ( $result->type == 'email' )
			{
				$company_ids[$result->contact_id]['email']++;
				if ( $company_ids[$result->contact_id]['email'] == 1 )
					usam_update_company_metadata($result->contact_id, 'email', $result->value  );
				else
					usam_update_company_metadata($result->contact_id, 'workemail', $result->value  );
			}
		}
	}
	unset($results[$key]);
}	
$wpdb->query("DELETE FROM `".USAM_TABLE_NEWSLETTER_USER_STAT."` WHERE communication=''"); 


$wpdb->query("DROP TABLE {$wpdb->prefix}usam_return_purchases"); 
$wpdb->query("DROP TABLE {$wpdb->prefix}usam_returned_products"); 

$fields = array( 
	array( 'name' => __('Мобильный телефон','usam'), 'code' => 'mobilephone','type' => 'contact', 'group' => 'communication', 'field_type' => 'mobile_phone', 'mask' => '' ),	
	array( 'name' => __('Email','usam'), 'code' => 'email','type' => 'contact', 'group' => 'communication', 'field_type' => 'email', 'mask' => ''),			
	
	array( 'name' => __('Телефон','usam'), 'code' => 'phone','type' => 'company', 'group' => 'communication', 'field_type' => 'mobile_phone', 'mask' => '' ),
	array( 'name' => __('Email','usam'), 'code' => 'email','type' => 'company', 'group' => 'communication', 'field_type' => 'email', 'mask' => '' ),					
);		
foreach ( $fields as $key => $field )
{
	$field['sort'] = $key+1;
	$id = usam_insert_property( $field );						
}			

$groups = array(    array( 'name' => __('Связаться','usam'), 'type' => 'company', 'code' => 'communication', 'sort' => 9 ),	);
foreach ( $groups as $group )
{			
	$wpdb->insert( usam_get_table_db('property_groups'), $group );
}
?>