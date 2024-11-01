<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class coupons_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'active' => ['title' => __('Активность', 'usam'), 'type' => 'select'], 
			'used' => ['title' => __('Использований', 'usam'), 'type' => 'numeric'], 	
		];
	}
}
?>