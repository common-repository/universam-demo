<?php

global $wp_roles, $wpdb;

USAM_Install::create_or_update_tables( array(usam_get_table_db('property_groups')) );

$contacts = usam_get_contacts(['fields' => 'id', 'cache_results' => true, 'conditions' => ['key' => 'firstname', 'value' => '', 'compare' => '=']]);
$orders = usam_get_orders( array('fields' => array('id', 'contact_id'),'contacts' => $contacts, 'cache_meta' => true ) );
foreach ( $orders as $order )
{
	$contact = array();
	$lastname = usam_get_order_metadata($order->id, 'billinglastname');	
	if ( $lastname )
		$contact['lastname'] = $lastname;	
	$firstname = usam_get_order_metadata($order->id, 'billingfirstname');	
	if ( $firstname )
		$contact['firstname'] = $firstname;	
	if( !empty($contact) )
		usam_update_contact( $order->contact_id, $contact );
}
$crm_contact_source = array( 
	array( 'name' => __('Свой контакт', 'usam'), 'id' => 'self' ), 
	array( 'name' => __('Сделал заказ', 'usam'), 'id' => 'order' ),
	array( 'name' => __('Зарегистрировался на Вашем сайте', 'usam'), 'id' => 'register' ),
	array( 'name' => __('Офлайн магазины', 'usam'), 'id' => 'offline' ),
	array( 'name' => __('Существующий клиент', 'usam'), 'id' => 'partner' ),
	array( 'name' => __('Звонок', 'usam'), 'id' => 'call' ),
	array( 'name' => __('Веб-сайт', 'usam'), 'id' => 'web' ),
	array( 'name' => __('Электронная почта', 'usam'), 'id' => 'email' ),
	array( 'name' => __('Конференция', 'usam'), 'id' => 'conference' ),
	array( 'name' => __('Яндекс', 'usam'), 'id' => 'yandex' ),	
	array( 'name' => __('Google', 'usam'), 'id' => 'google' ),			
	array( 'name' => __('Выставка', 'usam'), 'id' => 'trade_show' ),
	array( 'name' => __('Сотрудник', 'usam'), 'id' => 'employee' ),
	array( 'name' => __('Бывший сотрудник', 'usam'), 'id' => 'formeremployee' ),
	array( 'name' => __('Компания', 'usam'), 'id' => 'company' ),
	array( 'name' => __('Импортированный', 'usam'), 'id' => 'import' ),
	array( 'name' => __('Письмо', 'usam'), 'id' => 'mail' ),
	array( 'name' => __('Чат', 'usam'), 'id' => 'chat' ), 				
	array( 'name' => 'Instagram', 'id' => 'instagram' ),
	array( 'name' => 'Facebook', 'id' => 'facebook' ),
	array( 'name' => 'Telegram', 'id' => 'telegram' ),	
	array( 'name' => 'Viber', 'id' => 'viber' ),	
	array( 'name' => 'WhatsApp', 'id' => 'whatsapp' ),	
	array( 'name' => __('Вконтакте', 'usam'), 'id' => 'vk' ),
	array( 'name' => __('Однокласники', 'usam'), 'id' => 'ok' ),					
	array( 'name' => __('Другое', 'usam'), 'id' => 'orher' ),
		);		

update_option('usam_crm_contact_source',$crm_contact_source );