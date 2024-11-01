<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
class USAM_List_Table_payment_received_report extends USAM_Main_Report_List_Table
{		
	protected $status = 'all';
	protected $groupby_date = 'month';
			
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	function column_p( $item )
	{							
		$pointer = '';
		$p = '';
		if ( $item['p'] > 0 )
		{
			$pointer = "<span class = 'dashicons pointer_up'></span>";
			$p = "<span class ='item_status item_status_valid'>+{$item['p']}</span>";
		}
		elseif ( $item['p'] < 0 )
		{
			$pointer = "<span class = 'dashicons pointer_down'></span>";
			$p = "<span class ='item_status item_status_attention'>{$item['p']}</span>";
		}	
		return $pointer.$p; 
	}
		
	function get_columns()
	{
        $columns = [
			'date'   => __('Дата', 'usam'),			
			'cost'   => __('Сумма', 'usam'),
			'total'  => __('Количество', 'usam'),
			'p'  => __('Изменение %', 'usam'),
			'change_currency' => __('Изменение', 'usam'),
        ];
        return $columns;
    }
	
	function prepare_items() 
	{	
		$query = ['type' => 'payment_received', 'date_query' => [['after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true]], 'fields' => ['date_insert', 'sum', 'status', 'count'], 'order' => 'DESC', 'count_total' => false, 'groupby' => $this->groupby_date, 'orderby' => 'date_insert'];	
		$class = new USAM_Documents_Query( $query );	
		$documents = $class->get_results();
		$start_period = true;				
		$records = [];	
		$i = 0;			
		$m = 0;			
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{	
			$i++;
			$records[$i]['date'] = $j;
			$records[$i]['total'] = 0;	
			$records[$i]['cost'] = 0;		
			$records[$i]['p'] = 0;			
			$records[$i]['change_currency'] = 0;				
			$current_date = get_gmt_from_date(date("Y-m-d H:i:s",$j));
			foreach ( $documents as $key => $item )
			{			
				if ( $current_date > $item->date_insert )
				{							
					break;					
				}
				else
				{	
					$records[$i]['total'] += $item->count;		
					$records[$i]['cost'] += $item->sum;						
					if ( $m > 0 && $records[$i]['cost'] > 0 )
					{
						$records[$m]['change_currency'] = $records[$m]['cost'] - $records[$i]['cost'];						
						$records[$m]['p'] = round($records[$m]['change_currency'] * 100 / $records[$i]['cost'], 2);					
					}
					unset($documents[$key]);							
				}
			}
			$m = $i;			
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		}				
		$this->_column_headers = $this->get_column_info();		
		$this->items = $records;		
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, ['y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['cost'], 'label' => [ __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Стоимость', 'usam').': '.$this->currency_display( $item['cost'] ) ]] );
			foreach ( $item as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = 0;
				switch ( $key ) 
				{		
					case 'date' :
					case 'p' :		
					case 'change_currency' :						
						$this->results_line[$key] = '';
					break;					
					default:		
						if ( is_numeric($this->results_line[$key]) )
							$this->results_line[$key] += $value;
						else
							$this->results_line[$key] = '';
					break;			
				}				
			}
		}			
	}
	
	public function get_title_graph( ) 
	{
		return __('Сумма документов','usam');		
	}
}
?>