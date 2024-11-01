<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/shipped_list_table.php' );
class USAM_List_Table_delivery_documents extends USAM_Table_Shipped 
{		
	protected $status = 'expect_tc';
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox" />',				
			'id'          => __('Номер', 'usam'),
			'customer'    => __('Получатель', 'usam'),				
			'courier'     => __('Курьер', 'usam'),					
			'status'      => __('Статус', 'usam'),		
			'totalprice'  => __('Сумма', 'usam'),			
			'order_id'    => __('Заказ', 'usam'),	
		//	'track_id'    => __('Номер отслеживания', 'usam'),						
        );			
		if ( usam_check_current_user_role( 'courier' ) )
		{
			unset($columns['track_id']);
			unset($columns['courier']);
		}	
		elseif ( count($this->couriers) < 2 )
		{
			unset($columns['courier']);
		}
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