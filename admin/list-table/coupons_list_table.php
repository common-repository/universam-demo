<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/coupons_list_table.php' );
class USAM_List_Table_coupons extends USAM_Coupons_Table
{	      	
	protected $coupon_type = 'coupon';	
		
	function get_columns()
	{
        $columns = array(           
			'cb'          => '<input type="checkbox" />',				
			'coupon_code' => __('Код купона', 'usam'),
			'discount'    => __('Скидка', 'usam'),
			'interval'    => __('Интервал', 'usam'),			
			'customer'    => __('Владелец', 'usam'),		
			'description' => __('Описание', 'usam'),
			'is_used'     => __('Использован', 'usam'),			
			'date'        => __('Создан', 'usam'),			
        );		
        return $columns;
    }
}