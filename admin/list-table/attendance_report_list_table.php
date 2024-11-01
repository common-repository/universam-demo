<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class  USAM_List_Table_attendance_report extends USAM_Main_Report_List_Table
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
			'date'          => __('Дата', 'usam'),									
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
		$records = array();	
		
		require_once( USAM_FILE_PATH . '/includes/analytics/visits_query.class.php' );			
		$date_query = array( 'after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );		
		$select = $this->get_filter_value( 'weekday' );	
		if ( !empty($select) )
			$date_query['dayofweek'] = $select;			
		
		$query = array(
			'date_query' => array($date_query ),	
	//		'fields' => array('totalprice','date_insert', 'status'),
			'order' => 'DESC', 					
		);					
		$manager = $this->get_filter_value( 'manager' );	
		if ( !empty($manager) )
			$query['manager_id'] = (int)$manager;
		
		$visits = usam_get_visits( $query );
	
		$i = 0;	
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{			
			$records[$i]['date'] = $j;	
			$records[$i]['total_order'] = 0;
			$records[$i]['sum'] = 0;				
			$records[$i]['average_order'] = 0;
			$records[$i]['visits'] = 0;
			$records[$i]['page_views'] = 0;
			$records[$i]['visit_duration'] = 0;		
			$records[$i]['visit_duration_m'] = 0;					
			$records[$i]['conversion'] = 0;
			$records[$i]['page_depth'] = 0;
			$records[$i]['users'] = 0;
			$records[$i]['bounce_rate'] = 0;			
			$records[$i]['item_count'] = 0; 	
			$records[$i]['new_visitors'] = 0; 			
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			$users = array();
			foreach ( $visits as $key => $item )
			{						
				if ( $current_date >= $item->date_insert )
				{	
				if ( $records[$i]['visits'] )
					{
						$records[$i]['visit_duration_m'] = round($records[$i]['visit_duration_m']/$records[$i]['visits'],0);
						$records[$i]['bounce_rate'] = round($records[$i]['bounce_rate']/$records[$i]['visits'],2);
						$records[$i]['page_depth'] = round($records[$i]['page_views']/$records[$i]['visits'],2);						
					} 
					break;
				}
				else
				{					
					$time = strtotime($item->date_update)-strtotime($item->date_insert);
					$records[$i]['visits']++;
					if ( !isset($users[$item->contact_id]) )
					{
						$records[$i]['users']++;	
						$users[$item->contact_id] = 1;				
					}
					$users[$item->contact_id] = $item->contact_id;	
					$records[$i]['page_views'] += $item->views;
					$records[$i]['visit_duration_m'] += $time;
					if ( $item->views == 1 )
						$records[$i]['bounce_rate']++;	
					unset($visits[$key]);					
				}
			}			
			$i++;
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		} 	
		$this->items = $records;
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n("d.m.y", $item['date']), 'x_data' => (int)$item['visits'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']), __('Посещаемость', 'usam').': '.$item['visits']) ) );
		}
	}
	
	public function get_title_graph( ) 
	{
		return __('Визиты','usam');		
	}
}