<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
class USAM_List_Table_personnel_report extends USAM_Main_Report_List_Table
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
			'bonus'  => __('Бонусов получено', 'usam'),
			'products'  => __('Опубликованные товары', 'usam'),
			'orders'    => __('Обработанные заказы (кол-во)', 'usam'),
			'order_sum' => __('Обработанные заказы (сумма)', 'usam'),	
			'order_close'    => __('Закрытые заказы (кол-во)', 'usam'),
			'order_sum_close' => __('Закрытые заказы (сумма)', 'usam'),			
			'quantity_suggestion' => __('Предложения (кол-во)', 'usam'),
			'cost_suggestion'     => __('Предложения (Рубль)', 'usam'),				
			'quantity_invoice'    => __('Счета (кол-во)', 'usam'),
			'cost_invoice'        => __('Счета (Рубль)', 'usam'),
			'task'      => __('Заданий', 'usam'),
			'meeting'   => __('Встреч', 'usam'),
			'call'      => __('Звонков', 'usam'),			
			'event'     => __('Событий', 'usam'),				
        );
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->_column_headers = $this->get_column_info();	

		$records = array();				
		$manager = (int)$this->get_filter_value( 'manager' );					
				
		$date_query = array('after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );
		$weekday = $this->get_filter_value( 'weekday' );	
		if ( !empty($weekday) )
			$date_query['dayofweek'] = $weekday;		
		
		$query = array(
			'date_query' => array( $date_query ),		
		//	'status' => 'closed', 
			'fields' => array('totalprice','date_insert','status'),	
			'order' => 'DESC', 					
		);		
		if ( !empty($manager) )
			$query['manager_id'] = (int)$manager;
		$data_orders = usam_get_orders( $query );		
				
		$query = array(
			'date_query' => $date_query,	
			'cache_results' => false, 			
			'update_post_term_cache' => false, 		
			'stocks_cache' => false, 	
			'prices_cache' => false, 			
		);	
		if ( !empty($manager) )
			$query['author'] = $manager;			
		$data_products = usam_get_products( $query );
		
		$query = array(
			'date_query' => $date_query,
			'fields' => array('totalprice','date_insert', 'type'),	
			'order' => 'DESC', 					
		);			
		if ( !empty($manager) )
			$query['manager_id'] = $manager;		
		$documents = usam_get_documents( $query );	
		
		$query = array(
			'date_query' => $date_query,	
			'cache_results' => false, 	
			'fields' => array('sum','date_insert'),	
			'order' => 'DESC', 				
		);
		if ( !empty($manager) )
			$query['user_id'] = $manager;	
		else
		{
			$subordinates = usam_get_subordinates( );		
			$subordinates[] = get_current_user_id();	
			$query['user_id'] = array_unique($subordinates);
		}		
		$bonuses = usam_get_bonuses( $query );
		
		$date_query = array('column' => 'date_completion', 'after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );		
		if ( !empty($weekday) )
			$date_query['dayofweek'] = $weekday;	
		$query = array(
			'date_query' => $date_query,
			'fields' => array('type','date_completion'),	
			'links_query' => [['object_type' => 'contact']],
			'order' => 'DESC', 	
			'type' => array('meeting','call','task','event'),
		);				
		if ( !empty($manager) )
			$query['author'] = $manager;		
		$events = usam_get_events( $query );
		
		$i = 0;	
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{			
			$records[$i]['date'] = $j;	
			$records[$i]['orders'] = 0;				
			$records[$i]['products'] = 0;		
			$records[$i]['order_close'] = 0;				
			$records[$i]['order_sum_close'] = 0;			
			$records[$i]['order_sum'] = 0;	
			$records[$i]['quantity_suggestion'] = 0;	
			$records[$i]['cost_suggestion'] = 0;	
			$records[$i]['quantity_invoice'] = 0;	
			$records[$i]['cost_invoice'] = 0;		
			$records[$i]['meeting'] = 0;	
			$records[$i]['call'] = 0;	
			$records[$i]['task'] = 0;	
			$records[$i]['event'] = 0;	
			$records[$i]['bonus'] = 0;				
			$current_date = date( "Y-m-d H:i:s",$j);
			foreach ( $data_orders as $key => $item )
			{						
				if ( $current_date >= $item->date_insert )
				{	
					break;
				}
				else
				{					
					if ( $item->status == 'closed' )
					{
						$records[$i]['order_close']++;	
						$records[$i]['order_sum_close'] += $item->totalprice;	
					}				
					$records[$i]['orders']++;	
					$records[$i]['order_sum'] += $item->totalprice;							
					unset($data_orders[$key]);					
				}
			}	
			$current_date_gmt = get_gmt_from_date(date( "Y-m-d H:i:s",$j));			
			foreach ( $data_products as $key => $item )
			{			
				if ( $current_date_gmt >= $item->post_date )
				{	
					break;
				}
				else
				{	
					$records[$i]['products'] ++;	
					unset($data_products[$key]);					
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
				if ( $current_date >= $item->date_completion )
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
			foreach ( $bonuses as $key => $item )
			{										
				if ( $current_date >= $item->date_insert )
				{
					break;
				}
				else
				{			
					$records[$i]['bonus'] += $item->sum;	
					unset($bonuses[$key]);					
				}
			}
			$i++;
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		}			
		$this->items = $records;
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['products'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Количество', 'usam').': '.$this->currency_display( $item['products'] )) ) );
		}			
	}
	
	public function get_title_graph( ) 
	{
		return __('Опубликованные товары','usam');		
	}
}