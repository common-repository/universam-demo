<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_product_work_report extends USAM_Main_Report_List_Table
{	
    protected $groupby_date = 'week';
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	public function column_date( $item ) 
	{	
		$timestamp = (int) $item['date'];
		echo date( 'd.m.Y', $timestamp );			
	}
	
	function get_columns()
	{
        $columns = array(   			
			'date'      => __('Дата', 'usam'),			
			'publish_date'  => __('Опубликованные', 'usam'),
			'update_date'  => __('Измененные', 'usam'),
        );
        return $columns;
    }
	
	function prepare_items() 
	{	
		$this->_column_headers = $this->get_column_info();	
									
		$date_query = array( 'after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );		
		$select = $this->get_filter_value( 'weekday' );	
		if ( !empty($select) )
			$date_query['dayofweek'] = $select;	
		
		$query = array(
			'date_query' => array( $date_query ),	
			'cache_results' => false, 			
			'update_post_term_cache' => false, 		
			'stocks_cache' => false, 	
			'prices_cache' => false, 				
		);			
		$select = $this->get_filter_value( 'manager' );
		if ( !empty($select) )
			$query['author'] = (int)$select;			
		$products = usam_get_products( $query );		
		
		$i = 0;	
		$records = array();	
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{			
			$records[$i]['date'] = $j;	
			$records[$i]['publish_date'] = 0;	
			$records[$i]['update_date'] = 0;	
			foreach ( $products as $key => $item )
			{			
				if ( $j > strtotime($item->post_date) )
				{	
					break;
				}
				else
				{						
			//		if ( $j <= strtotime($item->post_modified) )									
						$records[$i]['update_date'] ++;											
					$records[$i]['publish_date'] ++;						
					unset($products[$key]);	
				}
			}				
			foreach ( $products as $key => $item )
			{			
				if ( $j > strtotime($item->post_modified) )
				{	
					break;
				}
				else
				{						
				//	if ( $j <= strtotime($item->post_modified) )									
						$records[$i]['update_date'] ++;											
				//	$records[$i]['publish_date'] ++;						
				//	unset($products[$key]);	
				}
			}	
			$i++;
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		}
		$this->items = $records;
		
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['publish_date'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Количество', 'usam').': '.$item['publish_date'] )) );
		}		
	}
	
	public function get_title_graph( ) 
	{
		return __('Опубликованные товары','usam');		
	}
}