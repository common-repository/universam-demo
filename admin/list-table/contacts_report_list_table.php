<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_contacts_report extends USAM_Main_Report_List_Table
{		
	protected $status = 'all';
	protected $groupby_date = 'month';
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
		
	function get_columns()
	{	
        $columns = array(  		
			'date'                      => __('Дата', 'usam'),
			'new'                       => __('Количество новых', 'usam'),	
			'user_registered'           => __('Регистраций', 'usam'),		
			'number_orders'             => __('Заказов (кол-во)', 'usam'),	
			'cost_order'                => __('Заказов (Рубль)', 'usam'),						
			'quantity_suggestion'       => __('Предложения (кол-во)', 'usam'),
			'cost_suggestion'           => __('Предложения (Рубль)', 'usam'),				
			'quantity_invoice'          => __('Счета (кол-во)', 'usam'),
			'cost_invoice'              => __('Счета (Рубль)', 'usam'),
			'meeting'                   => __('Встреч', 'usam'),
			'call'                      => __('Звонков', 'usam'),
			'task'                      => __('Заданий', 'usam'),
			'event'                     => __('Событий', 'usam'),	
        );
        return $columns;
    }
	
	function prepare_items() 
	{		
		global $wpdb;
		
		$this->_column_headers = $this->get_column_info();	
		$records = array();	
				
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );				
		$date_query = ['after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true];		
		$select = $this->get_filter_value( 'weekday' );	
		if ( !empty($select) )
			$date_query['dayofweek'] = $select;			
		
		$query = [
			'date_query' => [$date_query],	
			'fields' => ['number_orders', 'date_insert', 'total_purchased'],	
			'add_fields' => ['user_registered'],
			'order' => 'DESC', 
			'status__not_in' => ['temporary'], 				
		];					
		$selected = $this->get_filter_value( 'manager' );	
		if ( $selected )
			$query['manager_id'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value( 'status' );
		if ( $selected )
			$query['status_contacts'] = array_map('sanitize_title', (array)$selected);
		$selected = $this->get_filter_value( 'group' );
		if ( $selected )
			$query['groups_contacts'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value( 'contacts_source' );
		if ( $selected )
			$query['source_contacts'] = array_map('sanitize_title', (array)$selected);		
		$selected = $this->get_filter_value( 'gender' );
		if ( $selected )
			$query['gender_contacts'] = array_map('sanitize_title', (array)$selected);		
		$selected = $this->get_filter_value( 'company' );
		if ( $selected )
			$query['company_contacts'] = array_map('sanitize_title', (array)$selected);		
		$selected = $this->get_filter_value( 'age' );
		if ( $selected )
		{
			if ( !empty($selected) )
				$values = explode('|',$selected);
			if ( !empty($values[0]) )
				$query['from_age_contacts'] = intval($values[0]);
			if ( !empty($values[1]) )
				$query['to_age_contacts'] = intval($values[1]);
		}			
		$items = usam_get_contacts( $query );
					
		$query = array(
			'date_query' => $date_query,
			'customer_type' => 'contact', 
			'fields' => array('totalprice','date_insert', 'type'),
			'order' => 'DESC', 					
		);				
		$documents = usam_get_documents( $query );			
		
		$query = array(
			'date_query' => $date_query,
			'fields' => ['type','date_insert'],	
			'links_query' => [['object_type' => 'contact']],
			'order' => 'DESC', 					
		);				
		$events = usam_get_events( $query );	

		$all_in_work = array();
		$order_statuses = usam_get_object_statuses( );	
		foreach ( $order_statuses as $key => $status )		
			if ( !$status->close )
				$all_in_work[] = $key;			
		
		$i = 0;	
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{			
			$records[$i]['date'] = $j;
			$records[$i]['number_orders'] = 0;		
			$records[$i]['cost_order'] = 0;				
			$records[$i]['new'] = 0;
			$records[$i]['user_registered'] = 0;			
			$records[$i]['quantity_suggestion'] = 0;	
			$records[$i]['cost_suggestion'] = 0;	
			$records[$i]['quantity_invoice'] = 0;	
			$records[$i]['cost_invoice'] = 0;		
			$records[$i]['meeting'] = 0;	
			$records[$i]['call'] = 0;	
			$records[$i]['task'] = 0;	
			$records[$i]['event'] = 0;			
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));				
			foreach ( $items as $key => $item )
			{						
				if ( $current_date >= $item->date_insert )
				{	
					break;
				}
				else
				{					
					$records[$i]['number_orders'] += $item->number_orders;
					$records[$i]['cost_order'] += $item->total_purchased;
					$records[$i]['new']++;					
					if ( $item->user_registered )
						$records[$i]['user_registered']++;					
					unset($items[$key]);					
				}
			}	
			foreach ( $documents as $key => $item )
			{						
				if ( $current_date >= $item->date_insert )
				{	
					break;
				}
				else
				{					 
					if ($item->type == 'suggestion' )
					{
						$records[$i]['quantity_suggestion']++;	
						$records[$i]['cost_suggestion'] += $item->totalprice;							
					}
					elseif ( $item->type == 'invoice' )
					{
						$records[$i]['quantity_invoice']++;	
						$records[$i]['cost_invoice'] += $item->totalprice;												
					}
					unset($documents[$key]);					
				}
			}
			foreach ( $events as $key => $item )
			{
				if ( $current_date >= $item->date_insert )
				{	
					break;
				}
				else
				{					
					if ( empty($records[$i][$item->type]) )
						$records[$i][$item->type] = 1;
					else
						$records[$i][$item->type]++;		
					unset($events[$key]);					
				}
			}
			$i++;
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		}
		$this->items = $records;
		foreach ( $this->items as $key => $item )
		{			
			array_unshift($this->data_graph, ['y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['cost_order'], 'label' => [__('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Сумма', 'usam').': '.$this->currency_display( $item['cost_order'] ) ]]);
			foreach ( $item as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = 0;
				switch ( $key ) 
				{		
					case 'date' :
						$this->results_line[$key] = '';
					break;			
					default:			
						$this->results_line[$key] += $value;
					break;			
				}				
			}
		}	
	}
	
	public function get_title_graph( ) 
	{
		return __('Продажи','usam');		
	}
}
?>