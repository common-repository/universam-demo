<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_company_report extends USAM_Main_Report_List_Table
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
			'quantity_order'            => __('Заказов (кол-во)', 'usam'),	
			'cost_order'                => __('Заказов (Рубль)', 'usam'),	
			'quantity_pending'          => __('Заказов в работе (кол-во)', 'usam'),
			'cost_pending'              => __('Заказов в работе (Рубль)', 'usam'),
			'percent_pending'           => __('В ожидании (%)', 'usam'),			
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
		$this->_column_headers = $this->get_column_info();	
		$records = array();	
		$company_id = (int)$this->get_filter_value( 'company' );
		if ( !empty($company_id) )
		{			
			require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );				
			$date_query = array( 'after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );		
			$select = $this->get_filter_value( 'weekday' );	
			if ( !empty($select) )
				$date_query['dayofweek'] = $select;			
			
			$query = array(
				'date_query' => array($date_query ),	
				'fields' => array('totalprice','date_insert', 'status'),	
				'companies' => $company_id,	
				'order' => 'DESC', 					
			);					
			$manager = $this->get_filter_value( 'manager' );	
			if ( !empty($manager) )
				$query['manager_id'] = (int)$manager;
			
			$data_orders = usam_get_orders( $query );
		
			$query = array(
				'date_query' => $date_query,
				'fields' => array('totalprice','date_insert', 'type'),	
				'companies' => $company_id,	
				'order' => 'DESC', 					
			);				
			$documents = usam_get_documents( $query );				
			
			$query = array(
				'date_query' => $date_query,
				'fields' => array('type','date_insert'),	
				'links_query' => [['object_type' => 'company', 'object_id' => $company_id]],
				'order' => 'DESC', 					
			);				
			$events = usam_get_events( $query );	

			$types = usam_get_events_types( );
			$all_in_work = usam_get_object_statuses( array('type' => array_keys($types), 'fields' => 'internalname', 'close' => 0 ) );		
			
			$i = 0;	
			for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
			{			
				$records[$i]['date'] = $j;
				$records[$i]['quantity_order'] = 0;		
				$records[$i]['cost_order'] = 0;							
				$records[$i]['quantity_pending'] = 0;		
				$records[$i]['cost_pending'] = 0;	
				$records[$i]['percent_pending'] = 0;					
				$records[$i]['quantity_suggestion'] = 0;	
				$records[$i]['cost_suggestion'] = 0;	
				$records[$i]['quantity_invoice'] = 0;	
				$records[$i]['cost_invoice'] = 0;		
				$records[$i]['meeting'] = 0;	
				$records[$i]['call'] = 0;	
				$records[$i]['task'] = 0;	
				$records[$i]['event'] = 0;							
				$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
				foreach ( $data_orders as $key => $item )
				{						
					if ( $current_date >= $item->date_insert )
					{	
						break;
					}
					else
					{					
						$records[$i]['quantity_order']++;	
						$records[$i]['cost_order'] += $item->totalprice;		

						if ( in_array($item->status, $all_in_work) )
						{
							$records[$i]['quantity_pending']++;	
							$records[$i]['cost_pending'] += $item->totalprice;	
							$records[$i]['cost_order'] += $item->totalprice;		
						}
						unset($data_orders[$key]);					
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
	
			foreach ( $this->items as $item )
			{			
				array_unshift($this->data_graph, array( 'y_data' => date_i18n("d.m.y", $item['date']), 'x_data' => $item['cost_order'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']), __('Сумма', 'usam').': '.$this->currency_display( $item['cost_order'] )) ) );
			}		
		}
	}
	
	public function get_title_graph( ) 
	{
		return __('Продажи','usam');		
	}
}
?>