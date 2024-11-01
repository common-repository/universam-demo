<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/bonus_cards_table.php' );
require_once( USAM_FILE_PATH . '/includes/customer/bonus_cards_query.class.php');
class USAM_List_Table_bonus_cards extends USAM_Table_bonus_cards 
{	
	function get_columns()
	{
        $columns = array(  
			'cb'                => '<input type="checkbox">',			
			'code'              => __('Код карты', 'usam'),				
			'customer'          => __('Клиент', 'usam'),	
			'status'            => __('Статус', 'usam'),				
			'sum'               => __('Сумма', 'usam'),		
			'percent'           => __('Процент', 'usam'),
			'date'              => __('Дата', 'usam'),			
        ); 
        return $columns;
    }
	
	function prepare_items() 
	{				
		$this->get_query_vars();				
		
		if ( $this->status != 'all' )
			$this->query_vars['status'] = $this->status;			
					
		$selected = $this->get_filter_value( 'user_id' );
		if ( $selected )		
		{	
			$this->query_vars['user_id'] = sanitize_title($selected);		
		}		
		if ( empty($this->query_vars['include']) )
		{		
			$this->get_digital_interval_for_query( array('sum' ) );
		}
		$query = new USAM_Bonus_Cards_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}			
	}
}