<?php
require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_payment_report extends USAM_Main_Report_List_Table
{		
	protected $status = 'all';
	protected $groupby_date = 'month';
			
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	function no_items() 
	{
		_e( 'Оплаты не найдены', 'usam');
	}	
		
	function get_columns()
	{
        $columns = array(  		
			'date'                      => __('Дата', 'usam'),
			'total'                     => __('Количество', 'usam'),	
			'cost'                      => __('Стоимость всех (Рубль)', 'usam'),			
			'quantity_payment'          => __('Оплаченые(кол-во)', 'usam'),
			'percent_payment'           => __('Оплаченые (%)', 'usam'),
			'payment'                   => __('Оплаченые (Рубль)', 'usam'),			
			'quantity_unpaid'           => __('Не оплаченные (кол-во)', 'usam'),
			'cost_unpaid'               => __('Не оплаченные (Рубль)', 'usam'),			
			'percent_unpaid'            => __('Не оплаченные (%)', 'usam'),		
			'quantity_pending'          => __('В ожидании (кол-во)', 'usam'),
			'cost_pending'              => __('В ожидании (Рубль)', 'usam'),
			'percent_pending'           => __('В ожидании (%)', 'usam'),
			'quantity_refunded'         => __('Платеж возвращен (кол-во)', 'usam'),
			'cost_refunded'             => __('Платеж возвращен (Рубль)', 'usam'),		
			'percent_refunded'          => __('Платеж возвращен (%)', 'usam'),		
						
        );
        return $columns;
    }
	
	function prepare_items() 
	{	
		$query = array( 'date_query' => array( array('after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true )),
			'fields' => array('date_insert', 'sum', 'status'),	
			'order' => 'DESC', 
			'count_total' => false,			
		);		
		$payments = new USAM_Payments_Query( $query );	
		$result_payments = $payments->get_results();

		$start_period = true;				
		$records = array();	
		$i = 0;				
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{	
			$i++;
			$records[$i]['date'] = $j;
			$records[$i]['total'] = 0;	
			$records[$i]['cost'] = 0;	
			$records[$i]['quantity_payment'] = 0; 
			$records[$i]['percent_payment'] = 0;		
			$records[$i]['payment'] = 0;
			$records[$i]['quantity_unpaid'] = 0;
			$records[$i]['cost_unpaid'] = 0;			
			$records[$i]['percent_unpaid'] = 0;
			$records[$i]['quantity_pending'] = 0;
			$records[$i]['cost_pending'] = 0;
			$records[$i]['percent_pending'] = 0;		
			$records[$i]['quantity_refunded'] = 0;	
			$records[$i]['cost_refunded'] = 0;	
			$records[$i]['percent_refunded'] = 0;		
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			foreach ( $result_payments as $key => $item )
			{			
				if ( $current_date > $item->date_insert )
				{							
					break;					
				}
				else
				{	
					$records[$i]['total']++;
					$records[$i]['cost'] += $item->sum;	
					switch ( $item->status ) 
					{					
						case 1: //Не оплачено
							$records[$i]['quantity_unpaid']++;
							$records[$i]['cost_unpaid'] += $item->sum;
						break;			
						case 2: //Отклонено
						
						break;						
						case 3:	 //Оплачено
							$records[$i]['quantity_payment']++;
							$records[$i]['payment'] += $item->sum;
						break;						
						case 4: //Платеж возвращен
							$records[$i]['quantity_refunded'] ++;
							$records[$i]['cost_refunded'] += $item->sum;
						break;
						case 5://Ошибка оплаты
							
						break;		
						case 6: //В ожидании 
							$records[$i]['quantity_pending']++;
							$records[$i]['cost_pending'] += $item->sum;
						break;							
					}					
					unset($result_payments[$key]);							
				}
			}	
			if ( $records[$i]['total'] > 0 )
			{
				if ( $records[$i]['quantity_payment'] > 0 ) 		
					$records[$i]['percent_payment'] = round($records[$i]['quantity_payment'] / $records[$i]['total']*100, 1);					
				if ( $records[$i]['quantity_unpaid'] > 0 )
					$records[$i]['percent_unpaid'] = round($records[$i]['quantity_unpaid'] / $records[$i]['total'] *100, 1);
				if ( $records[$i]['quantity_refunded'] > 0 )
					$records[$i]['percent_refunded'] = round($records[$i]['quantity_refunded'] / $records[$i]['total'] *100, 1);
				if ( $records[$i]['quantity_pending'] > 0 )
					$records[$i]['percent_pending'] = round($records[$i]['quantity_pending'] / $records[$i]['total'] *100, 1);
			}				
			$j = strtotime("-1 ".$this->groupby_date, $j);		
		}				
		$this->_column_headers = $this->get_column_info();		
		$this->items = $records;
		
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['total'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Стоимость', 'usam').': '.$this->currency_display( $item['total'] ) )) );
		}		
	}
	
	public function get_title_graph( ) 
	{
		return __('Стоимость всех оплат','usam');		
	}
}
?>