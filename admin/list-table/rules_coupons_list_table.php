<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_rules_coupons extends USAM_List_Table 
{	
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_type($item)
	{		
		echo usam_get_site_trigger( $item['rule_type'] ); 
	}

	function column_title( $item ) 
	{		
		$this->row_actions_table($this->item_edit($item['id'], $item['title'], 'rule_coupon'), $this->standart_row_actions($item['id'], 'rule_coupon', ['copy' => __('Копировать', 'usam')]) );
	}
	
	function column_discount( $item ) 
    {	
		switch ( $item['discount_type'] ) 
		{
			case '0':	
				echo usam_get_formatted_price( esc_attr($item['discount']) );		
			break;
			case '1':	
				echo round($item['discount'], 2).'%';		
			break;
			case '2':	
				echo round($item['discount'], 2).'% '.__('от заказа как фиксированная скидка в купоне', 'usam');
			break;		
			case '3':	
				_e( 'Бесплатная доставка', 'usam');
			break;				
		}	
	}
   
	function get_sortable_columns() 
	{
		$sortable = array(
			'discount'  => array('discount', false),	
			'active'    => array('active', false),			
			'day'       => array('day', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',				
			'title'          => __('Название правила', 'usam'),
			'active'         => __('Активность', 'usam'),
			'discount'       => __('Скидка', 'usam'),			
			'type'           => __('Триггер', 'usam'),
        );		
        return $columns;
    }
	
	public function get_number_columns_sql()
    {       
		return array('day');
    }
	
	function prepare_items() 
	{				
		$this->get_query_vars();
		$this->items = usam_get_coupons_rules( $this->query_vars );
		if ( $this->per_page )
		{
			$this->total_items = count(usam_get_coupons_rules( ));
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}