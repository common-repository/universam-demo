<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class payment_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'payment' => ['title' => __('Способы оплаты', 'usam'), 'type' => 'checklists'], 
			'sum' => ['title' => __('Сумма', 'usam'), 'type' => 'numeric'], 			
		];
	}		
}
?>