<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_bonus_cards_report extends USAM_Main_Report_List_Table
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
		//	'number_cards'              => __('Новых карт', 'usam'),
			'bonuses_received'          => __('Получено бонусов', 'usam'),	
			'written_bonuses'           => __('Списанных бонусов', 'usam'),			
			'cost_order'                => __('Количество заказов', 'usam'),	
        );
        return $columns;
    }
	
	function prepare_items() 
	{		
		global $wpdb;
		
		$this->_column_headers = $this->get_column_info();	
		$records = array();	
		
		$date_query = ['after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true];	
		$query = [
			'date_query' => [$date_query],	
			'orderby' => 'date_insert',
			'order' => 'DESC', 			
		];	
		$selected = $this->get_filter_value( 'triggers' );	
		if ( $selected )
			$query['transaction_code'] = array_map('sanitize_title', (array)$selected);

		require_once( USAM_FILE_PATH . '/includes/customer/bonuses_query.class.php'  );
		$bonuses = usam_get_bonuses( $query );
		
		$i = 0;	
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{			
			$records[$i]['date'] = $j;
			$records[$i]['number_cards'] = 0;						
			$records[$i]['written_bonuses'] = 0;		
			$records[$i]['bonuses_received'] = 0;	
			$records[$i]['cost_order'] = 0;		
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));				
			foreach ( $bonuses as $key => $item )
			{						
				if ( $current_date >= $item->date_insert )
				{	
					break;
				}
				else
				{					
					$records[$i]['number_cards']++;	
					if ( $item->type_transaction )
						$records[$i]['written_bonuses'] += $item->sum;	
					else
						$records[$i]['bonuses_received'] += $item->sum;	
					if ( $item->order_id )
						$records[$i]['cost_order']++;				
					unset($bonuses[$key]);					
				}
			}	
			$i++;
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		}
		$this->items = $records;

		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['bonuses_received'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Сумма', 'usam').': '.$this->currency_display( $item['bonuses_received'] ) )) );
		}	
	}
	
	public function get_title_graph( ) 
	{
		return __('Получено бонусов','usam');		
	}
}
?>