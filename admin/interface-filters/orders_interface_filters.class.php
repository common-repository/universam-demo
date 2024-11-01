<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface-filters/orders_interface_filters.class.php' );
class Orders_Interface_Filters extends Document_Orders_Interface_Filters
{	
	public function get_sort( ) 
	{
		return ['date-desc' => __('Сначала новые', 'usam'), 'date-asc' => __('Сначала старые', 'usam'), 'date_paid-desc' => __('По дате оплаты &#8595;', 'usam'), 'date_paid-asc' => __('По дате оплаты &#8593;', 'usam'), 'totalprice-asc' => __('По сумме &#8593;', 'usam'), 'totalprice-desc' => __('По сумме &#8595;', 'usam'), 'source-desc' => __('По источнику &#8595;', 'usam'), 'source-asc' => __('По источнику &#8593;', 'usam'), 'date_status_update-desc' => __('Обновление статуса &#8595;', 'usam'), 'date_status_update-asc' => __('Обновление статуса &#8593;', 'usam')];	
	}
}
?>
