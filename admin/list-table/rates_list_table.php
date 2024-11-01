<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/directory/currency_rates_query.class.php' );
class USAM_List_Table_Rates extends USAM_List_Table
{	
    protected $pimary_id = 'basic_currency';

	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}					
	
	function column_basic_currency( $item ) 
    {					
		$this->row_actions_table( $item->basic_currency, $this->standart_row_actions($item->id, 'rate') );	
	}		
	
	function column_official_rate( $item ) 
	{				
		$rates = new USAM_ExchangeRatesCBRF( );	
		echo $rates->GetCrossRate( $item->basic_currency, $item->currency );
	}	
			
	function column_autoupdate( $item ) 
	{						
		$this->logical_column( $item->autoupdate );
	}
  
	function get_sortable_columns()
	{
		$sortable = array(
			'basic_currency'   => array('basic_currency', false),
			'currency'         => array('currency', false),
		);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',	
			'basic_currency'   => __('Базовая валюта', 'usam'),
			'currency'         => __('Валюта', 'usam'),
			'rate'             => __('Курс', 'usam'),	
			'autoupdate'       => __('Автообновление', 'usam'),			
			'markup'           => __('Наценка', 'usam'),				
			'date_update'      => __('Дата обновления', 'usam'),	
			'official_rate'    => __('Курс ЦБ', 'usam'),				
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{					
		$this->get_query_vars();			
		if ( empty($this->query_vars['include']) )
		{			
			$selected = $this->get_filter_value( 'basic_currency' );
			if ( $selected )
				$this->query_vars['basic_currency'] = array_map('sanitize_title', $selected);	
			
			$selected = $this->get_filter_value( 'currency' );
			if ( $selected )
				$this->query_vars['currency'] = array_map('sanitize_title', $selected);		
			
			$selected = $this->get_filter_value( 'autoupdate' );
			if ( $selected )
				$this->query_vars['autoupdate'] = $selected;	
		} 
		$query = new USAM_Currency_Rates_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}