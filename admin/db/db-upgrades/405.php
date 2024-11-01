<?php

global $wpdb;
USAM_Install::create_or_update_tables( array(USAM_TABLE_BONUS_TRANSACTIONS)  );

$statuses_bonus = array( 	
	array(    
			'internalname'   => 'locked_order_completion',
			'title'          => __('Блокированные до завершения заказа', 'usam'),
			'name'           => __('Блокированные', 'usam'),						
			'sort'           => 1,
			'number'         => 1,						
		),	
	array(    
			'internalname'   => 'locked_time',
			'title'          => __('Блокированные до истечение времени возврата товара', 'usam'),
			'name'           => __('Блокированные', 'usam'),						
			'sort'           => 2,
			'number'         => 2,						
		),		
	array(    
			'internalname'   => 'available',
			'title'          => __('Доступные для использования', 'usam'),
			'name'           => __('Доступные', 'usam'),						
			'sort'           => 3,
			'number'         => 3,							
		),			
	array(    
			'internalname'   => 'locked_payment',
			'title'          => __('Блокированные для оплаты', 'usam'),
			'name'           => __('Блокированные', 'usam'),						
			'sort'           => 4,
			'number'         => 4,							
		),
	array(    
			'internalname'   => 'blocked_after_use',
			'title'          => __('Блокированные после использования до завершения заказа', 'usam'),
			'name'           => __('Блокированные', 'usam'),						
			'sort'           => 5,
			'number'         => 5,						
		),
	array(    
			'internalname'   => 'used',
			'title'          => __('Использованые', 'usam'),
			'name'           => __('Использованые', 'usam'),						
			'sort'           => 6,
			'number'         => 6,						
		),							
	);	
require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );	
$bonuses = usam_get_bonuses();
foreach ( $bonuses as $bonus )
{	
	foreach ( $statuses_bonus as $status_bonus )
	{
		if ( $status_bonus['number'] == $bonus->status )
			usam_update_bonus( $bonus->id, array( 'status' => $status_bonus['internalname'] ) );
	}
}
?>