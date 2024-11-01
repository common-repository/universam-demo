<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/orders_table.php' );
class USAM_Order_Table extends USAM_Table_Orders 
{			
	public function get_columns()
	{
		return array(
			'cb'         => '<input type="checkbox" />',
			'id'         => __('Номер', 'usam'),			
			'totalprice' => __('Сумма', 'usam'),			
			'status'     => __('Статус', 'usam'),			
			'date'       => __('Дата', 'usam'),		
			'select'     => __('Выбрать', 'usam'),				
		);
	}
	
	function column_select( $item ) 
    {		
		echo "<a href=''>".__('добавить', 'usam')."</a>";
	}		
	
	public function column_id( $item ) 
	{
		echo '<span class="js-object-value">'.$item->id.'</span>';
	}
		
	public function column_totalprice( $item ) 
	{
		echo usam_get_formatted_price( $item->totalprice, ['type_price' => $item->type_price]);
	}
	
	public function column_status( $item ) 
	{			
		usam_display_status( $item->status, 'order' );
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "contact-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
}