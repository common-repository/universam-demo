<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/coupons_list_table.php' );
class USAM_List_Table_Certificates extends USAM_Coupons_Table
{	  
	protected $coupon_type = 'certificate';	

	function column_coupon_code( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, $this->coupon_type );	
		$this->row_actions_table( $item->coupon_code, $actions );	
	}		
			
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox">',				
			'coupon_code'    => __('Код сертификата', 'usam'),
			'discount'       => __('Скидка', 'usam'),
			'interval'       => __('Интервал', 'usam'),				
			'user_id'        => __('Владелец', 'usam'),				
			'date_insert'     => __('Создан', 'usam'),			
        );		
        return $columns;
    }
}