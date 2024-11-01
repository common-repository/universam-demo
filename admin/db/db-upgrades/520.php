<?php
global $wp_roles, $wpdb, $wp_rewrite;
	
				
delete_option('usam_search_option');	
USAM_Install::create_or_update_tables([USAM_TABLE_PAYMENT_HISTORY_META, USAM_TABLE_CHAT, USAM_TABLE_NEWSLETTER_TEMPLATE_META, USAM_TABLE_ACCOUNT_TRANSACTIONS, USAM_TABLE_BONUS_TRANSACTIONS, USAM_TABLE_PARSING_SITE_URL,USAM_TABLE_DISCOUNT_RULES, USAM_TABLE_MAILING_LIST_META, USAM_TABLE_GROUP_RELATIONSHIPS]);


$wpdb->query( "ALTER TABLE `".USAM_TABLE_PAYMENT_HISTORY_META."` DROP COLUMN `document_id`" );

$statuses = usam_get_object_statuses(['type' => 'payment']);
if ( empty($statuses) )
{
	$object_status = [
	// Документы оплаты		
		array('internalname' => '1', 'name' => __('Не оплачено', 'usam'), 'short_name' => __('Не оплаченные', 'usam'), 'active' => 1, 'close' => false, 'type' => 'payment', 'visibility' => true ),
		array('internalname' => '2', 'name' => __('Отклонено', 'usam'), 'short_name' => __('Отклоненные', 'usam'), 'active' => 1, 'close' => true, 'type' => 'payment', 'visibility' => false ),
		array('internalname' => '3', 'name' => __('Оплачено', 'usam'), 'short_name' => __('Оплаченные', 'usam'), 'active' => 1, 'close' => true, 'type' => 'payment', 'visibility' => true ),
		array('internalname' => '4', 'name' => __('Платеж возвращен', 'usam'), 'short_name' => __('Возвращенные', 'usam'), 'active' => 1, 'close' => true, 'type' => 'payment', 'visibility' => true ),
		array('internalname' => '5', 'name' => __('Ошибка оплаты', 'usam'), 'short_name' => __('Ошибка оплаты', 'usam'), 'active' => 1, 'close' => true, 'type' => 'payment', 'visibility' => false ),
		array('internalname' => '6', 'name' => __('В ожидании', 'usam'), 'short_name' => __('В ожидании', 'usam'), 'active' => 1, 'close' => false, 'type' => 'payment', 'visibility' => false ),
		array('internalname' => '7', 'name' => __('В обработке', 'usam'), 'short_name' => __('В обработке', 'usam'), 'active' => 1, 'close' => false, 'type' => 'payment', 'visibility' => false ),
		array('internalname' => '8', 'name' => __('Денежные средства зарезервированы', 'usam'), 'short_name' => __('Зарезервированные', 'usam'), 'active' => 1, 'close' => false, 'type' => 'payment', 'visibility' => false ),	

	// Документы отгрузки		
		array('internalname' => 'pending', 'name' => __('Ожидает обработку', 'usam'), 'short_name' => __('Ожидает обработку', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'packaging', 'name' => __('Комплектация заказа', 'usam'), 'short_name' => __('Комплектация', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'arrival', 'name' => __('Ожидаем приход товара', 'usam'), 'short_name' => __('Ожидаем приход', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'expect_tc', 'name' => __('Ожидание забора', 'usam'), 'short_name' => __('Ожидание забора', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'collected', 'name' => __('Ожидает вручения', 'usam'), 'short_name' => __('Ожидает вручения', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'referred', 'name' => __('Передан в службу доставки', 'usam'), 'short_name' => __('Передан в службу доставки', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'courier', 'name' => __('Передан курьеру', 'usam'), 'short_name' => __('Передан курьеру', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),
		array('internalname' => 'shipped', 'name' => __('Отгружен', 'usam'), 'short_name' => __('Отгружен', 'usam'), 'active' => 1, 'close' => true, 'type' => 'shipped', 'visibility' => true ),		
		array('internalname' => 'canceled', 'name' => __('Отменен', 'usam'), 'short_name' => __('Отменен', 'usam'), 'active' => 1, 'close' => true, 'type' => 'shipped', 'visibility' => true ),		
		array('internalname' => 'delivery_problem', 'name' => __('Проблема с доставкой', 'usam'), 'short_name' => __('Проблема с доставкой', 'usam'), 'active' => 1, 'close' => false, 'type' => 'shipped', 'visibility' => true ),				

	];	
	foreach ( $object_status as $key => $status )
	{ 
		$status['sort'] = $key+1;
		usam_insert_object_status( $status );
	}
}

$wp_rewrite->flush_rules();	