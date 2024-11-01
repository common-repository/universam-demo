<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class  USAM_List_Table_sources_report extends USAM_Main_Report_List_Table
{		
	public function column_visit_duration_m( $item ) 
	{		
		if ( $item['visit_duration_m'] >= 3600 )
			return date_i18n('H:i:s', $item['visit_duration_m'] );
		else
			return date_i18n('i:s', $item['visit_duration_m'] );
	}	
			
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	function get_columns()
	{
        $columns = array(   	
			'source'      => __('Источник трафика', 'usam'),			
			'visits'      => __('Визиты', 'usam'),	
			'page_views'    => __('Просмотры', 'usam'),	
			'users'         => __('Посетители', 'usam'),	
			'new_visitors'    => __('Доля новых посетителей', 'usam'),				
			'visit_duration_m' => __('Время на сайте', 'usam'),		
			'page_depth'     => __('Глубина просмотра', 'usam'),				
			'bounce_rate'    => __('Отказы', 'usam'),			
			'conversion'    => __('Конверсия', 'usam'),	
		);
        return $columns;
    }
	
	function prepare_items() 
	{				
		$this->_column_headers = $this->get_column_info();	
		
		require_once( USAM_FILE_PATH . '/includes/analytics/visits_query.class.php' );			
		$date_query = array( 'after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );		
		$select = $this->get_filter_value( 'weekday' );	
		if ( !empty($select) )
			$date_query['dayofweek'] = $select;			
		$query = array(
			'date_query' => array($date_query ),	
	//		'fields' => array('totalprice','date_insert', 'status'),
			'order' => 'DESC', 			
			'orderby' => 'source'
		);					
		$manager = $this->get_filter_value( 'manager' );	
		if ( !empty($manager) )
			$query['manager_id'] = (int)$manager;
		
		$visits = usam_get_visits( $query );		
		foreach ( $visits as $key => $item )
		{						
			$this->items[$item->source] = array();
			$this->items[$item->source]['source'] = $item->source;
			$this->items[$item->source]['total_order'] = 0;
			$this->items[$item->source]['sum'] = 0;				
			$this->items[$item->source]['average_order'] = 0;
			$this->items[$item->source]['visits'] = 0;
			$this->items[$item->source]['page_views'] = 0;
			$this->items[$item->source]['visit_duration'] = 0;		
			$this->items[$item->source]['visit_duration_m'] = 0;					
			$this->items[$item->source]['conversion'] = 0;
			$this->items[$item->source]['page_depth'] = 0;
			$this->items[$item->source]['users'] = 0;
			$this->items[$item->source]['bounce_rate'] = 0;			
			$this->items[$item->source]['item_count'] = 0; 	
			$this->items[$item->source]['new_visitors'] = 0; 				
			if ( $this->items[$item->source]['visits'] )
			{
			//	$this->items[$item->source]['visit_duration_m'] = round($this->items[$item->source]['visit_duration_m']/$this->items[$item->source]['visits'],0);
			//	$this->items[$item->source]['bounce_rate'] = round($this->items[$item->source]['bounce_rate']/$this->items[$item->source]['visits'],2);
			//	$this->items[$item->source]['page_depth'] = round($this->items[$item->source]['page_views']/$this->items[$item->source]['visits'],2);						
			} 
			else
			{					
				$time = strtotime($item->date_update)-strtotime($item->date_insert);
				$this->items[$item->source]['visits']++;
				if ( !isset($users[$item->contact_id]) )
				{
					$this->items[$item->source]['users']++;	
					$users[$item->contact_id] = 1;				
				}
				$users[$item->contact_id] = $item->contact_id;	
				$this->items[$item->source]['page_views'] += $item->views;
				$this->items[$item->source]['visit_duration_m'] += $time;
				if ( $item->views == 1 )
					$this->items[$item->source]['bounce_rate']++;	
			}
		}
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => $item['source'], 'x_data' => (int)$item['visits'], 'label' => array( __('Источник', 'usam').': '.$item['source'], __('Посещаемость', 'usam').': '.$item['visits']) ) );
		}
	}
	
	public function get_title_graph( ) 
	{
		return __('Визиты','usam');		
	}
}