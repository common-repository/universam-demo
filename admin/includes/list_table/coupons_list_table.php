<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/basket/coupons_query.class.php' );
class USAM_Coupons_Table extends USAM_List_Table 
{	   
	protected $is_bulk_edit = false;
	protected $order = 'desc';
	protected $orderby = 'id';
	protected $coupon_type = 'coupon';
	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'       => __('Удалить', 'usam'),
			'activate'     => __('Активировать', 'usam'),
			'deactivate'   => __('Отключить', 'usam'),
			'bulk_actions' => __('Открыть массовые действия', 'usam'),
		);
		return $actions;
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];			
	}
				
	function column_coupon_code( $item ) 
    {
		$name = $this->item_edit($item->id, $item->coupon_code, 'coupon');
		if ( $item->active )
			$name = "<span class='item_status_valid item_status'>".$name."</span>";
		$this->row_actions_table($name, $this->standart_row_actions( $item->id, 'coupon' ) );	
	}	
	
	function column_discount( $item ) 
    {			
		switch ( $item->is_percentage ) 
		{
			case '0':					
				echo usam_get_formatted_price( $item->value );		
			break;
			case '1':	
				echo round($item->value, 2).'%';		
			break;
			case '2':	
				_e( 'Бесплатная доставка', 'usam');
			break;			
		}	
	}
	
	function column_is_used( $item ) 
	{		
		if ( $item->max_is_used )
			echo sprintf(__('%s из %s','usam'), $item->is_used, $item->max_is_used);
		else
			echo $item->is_used;
	}
	   	
	function get_sortable_columns() 
	{
		$sortable = array(
			'name'        => array('name', false),		
			'discount'    => array('value', false),
			'is_used'     => array('is_used', false),
			'start'       => array('start', false),	
			'end_date'    => array('end_date', false),			
			);
		return $sortable;
	}
	
	function prepare_items() 
	{						
		$this->get_query_vars();
		$this->query_vars['cache_results'] = true;			
		$this->query_vars['coupon_type'] = $this->coupon_type;
		if ( $this->search == '' )
		{			
			$selected = $this->get_filter_value( 'active' );			
			if ( $selected !== null )
				$this->query_vars['active'] = intval($selected);
			else
				$this->query_vars['active'] = 'all';
			$this->get_digital_interval_for_query(  array('is_used') );			
		}
		else
			$this->query_vars['active'] = 'all';
		$query = new USAM_Coupons_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}