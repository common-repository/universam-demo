<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/shipped_list_table.php' );
class USAM_List_Table_shipping_documents extends USAM_Table_Shipped 
{			       
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox" />',				
			'id'          => __('Номер', 'usam'),
			'name'        => __('Способ доставки', 'usam'),						
			'status'      => __('Статус', 'usam'),		
			'totalprice'  => __('Сумма', 'usam'),
			'date'        => __('Дата', 'usam'),				
			'order_id'    => __('Заказ', 'usam')				
        );
		if ( $this->status != 'all_in_work' && $this->status != 'all' )
		{
			unset($columns['status']);
		}
		$user_id = get_current_user_id(); 
		if ( !user_can( $user_id, 'edit_shipped' ) )
			unset($columns['cb']);
        return $columns;
    }
}