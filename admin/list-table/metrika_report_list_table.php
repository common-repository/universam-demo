<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
require_once( USAM_FILE_PATH . '/includes/seo/yandex/metrika.class.php' );
class  USAM_List_Table_metrika_report extends USAM_Main_Report_List_Table
{				
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
			'total_order'   => __('Количество заказов', 'usam'),			
			'conversion'    => __('Конверсия', 'usam'),	
			'item_count'    => __('Количество товаров', 'usam'),
			'sum'           => __('Сумма заказов', 'usam'),		
			'average_order' => __('Средний заказ', 'usam'),		
		);
        return $columns;
    }
	
	function prepare_items() 
	{				
		$page = $this->get_pagenum();		
		$this->get_standart_query_parent( );	
		
		$start_date = strtotime($this->end_date_interval);
		$end_date = strtotime($this->start_date_interval);
	
		$date_query = array( 'after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true );		
		$select = $this->get_filter_value( 'weekday' );	
		if ( !empty($select) )
			$date_query['dayofweek'] = $select;	
	
		$query = [
			'groupby' => array( $this->groupby_date ), 	
			'date_query' => array($date_query ),
			'fields' => ['date_insert','count','sum','number_products','average_order'],
			'order' => 'DESC', 				
		];			
		$select = $this->get_filter_value( 'code_price' );
		if ( !empty($select) )
		{		
			$query['type_prices'] = sanitize_title($select);
		}	
		$all_orders = usam_get_orders( $query );			
		/*Получение данных из Яндекс Метрика*/				
		$metrika = new USAM_Yandex_Metrika();
		if ( !$metrika->auth() )
			return false;
		$result_yandex = $metrika->get_statistics(['date1' => date('Y-m-d', $end_date), 'date2' => date('Y-m-d', $start_date), 'group' => $this->groupby_date, 'limit' => 10000]);	
		$metrika->set_log_file();		
		$records = [];	
	
		$j = $this->time_calculation_interval_start;	
		$ok = true;
		$i = 0;
		while( $ok )
		{					
			$i++;			
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
			foreach ( $result_yandex as $key => $item )
			{
				if ( $j <= strtotime($item['from']) )
				{						
					$records[$i] = $item;
					unset($result_yandex[$key]);	
					break;
				}						
			}	
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			$records[$i]['date'] = $j;						
			foreach ( $all_orders as $key => $item )
			{		
				if ( $current_date < $item->date_insert )
				{
					$records[$i]['average_order'] = $item->average_order;				
					$records[$i]['sum'] = $item->sum;	
					$records[$i]['total_order'] = $item->count;
					$records[$i]['item_count'] = $item->number_products;		//Кол-во товаров	
				
					if ($records[$i]['visits'] > 0 && $item->count > 0)
						$records[$i]['conversion'] = round($item->count/$records[$i]['visits']*100,2);
					
					unset($all_orders[$key]);		
					break;
				}				
			}			
			$records[$i]['new_visitors'] = round($records[$i]['new_visitors'],2);					
			$records[$i]['visit_duration_m'] = date("i:s", mktime(0, 0, $records[$i]['visit_duration']));			
			
			$j = strtotime("-1 ".$this->groupby_date, $j);		
			if ( $end_date >= $j)
			{
				$j = $end_date;			
				$ok = false;
			}			
		}				
		if ( empty($records) )
			return false;
		
		$this->items = $records;
		$this->total_items = count($records);
		
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, ['y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['visits'], 'label' => [ __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Визиты', 'usam').': '.$this->currency_display( $item['visits'] )]]);		
			foreach ( $item as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = 0;
				switch ( $key ) 
				{		
					case 'date' :
						$this->results_line[$key] = '';
					break;			
					case 'visit_duration_m' :
						$this->results_line[$key] += date('i', strtotime($value.':00'));
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
		$count = count($this->items);
		$this->results_line['visit_duration_m'] = date("i:s", mktime(0, 0, round($this->results_line['visit_duration']/$count, 1)));
		$this->results_line['new_visitors'] = round($this->results_line['new_visitors']/$count, 2);			
		$this->results_line['page_depth'] = round($this->results_line['page_depth']/$count, 2);	
		$this->results_line['conversion'] = round($this->results_line['conversion']/$count, 2);		
		$this->results_line['average_order'] = round($this->results_line['average_order']/$count, 0);			
	}
	
	public function get_title_graph( ) 
	{
		return __('Визиты','usam');		
	}
}