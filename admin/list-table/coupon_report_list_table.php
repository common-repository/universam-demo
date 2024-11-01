<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_Coupon_report extends USAM_Main_Report_List_Table
{	    
	protected $groupby_date = 'month';
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	  
	function get_sortable_columns() 
	{
		$sortable = array(
			'name'          => array('name', false),
			'sales_amount'  => array('sales_amount', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   			
			'date'            => __('Дата', 'usam'),		
			'total'           => __('Количество', 'usam'),			
			'using'           => __('Использованных', 'usam'),
			'unused'          => __('Не использованных', 'usam'),
			'percent'         => __('%', 'usam'),			
			'cost'            => __('Сумма закказов', 'usam'),			
        );
        return $columns;
    }
	
	function prepare_items() 
	{		
		global $wpdb;
		
		$this->_column_headers = $this->get_column_info();
		
		$where = array( "c.coupon_type='coupon'" );
		$join  = array();
		
		$where[] = "c.start_date<='$this->end_date_interval'";
		$where[] = "c.start_date>='$this->start_date_interval'";		
					
		$join[] = " LEFT JOIN ".USAM_TABLE_ORDER_META." AS or_meta ON (c.coupon_code=or_meta.meta_value AND p.id=or_meta.order_id AND or_meta.meta_key='coupon_name')";	
		$join[] = " LEFT JOIN ".USAM_TABLE_ORDERS." AS p ON (p.status='closed')";
		
		$shipping = $this->get_filter_value( 'shipping' );
		$storage = $this->get_filter_value( 'storage' );
		if ( !empty($shipping) || !empty($storage) )
		{
			$join[] = " LEFT JOIN ( SELECT order_id, method, storage_pickup, storage FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." WHERE 1=1 ORDER BY date_insert DESC LIMIT 1) AS doc_ship ON (doc_ship.order_id=p.id)";			
			if ( !empty($shipping) )
				$where[] = "doc_ship.method='".absint($shipping)."'";
			if ( !empty($storage) )
				$where[] = "doc_ship.storage='".absint($storage)."'";
		}								
		$selects = array( 'c.value, c.start_date, c.is_used, p.totalprice');	

		$selects = implode( ', ', $selects );		
		$join = implode( ' ', $join );
		$where = implode( ' AND ', $where );
		
		$coupons = $wpdb->get_results("SELECT $selects FROM ".USAM_TABLE_COUPON_CODES." AS c $join WHERE $where ORDER BY c.id DESC");
		$records = array();	
		$i = 0;			
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{	
			$i++;			
			$records[$i]['date'] = $j;
			$records[$i]['total'] = 0; //Общее количество
			$records[$i]['cost'] = 0;				
			$records[$i]['percent'] = 0;		
			$records[$i]['coupon_discount'] = 0;
			$records[$i]['using'] = 0;	
			$records[$i]['unused'] = 0;	
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			foreach ( $coupons as $key => $item )
			{			
				if ( $current_date > $item->start_date )
				{					
					if ( $records[$i]['unused'] > 0 && $records[$i]['using'] > 0) 
						$records[$i]['percent'] = round($records[$i]['using'] / $records[$i]['unused'] *100, 1);
					break;
				}
				else
				{	
					$records[$i]['total']++;
					if ( $item->is_used ) 
						$records[$i]['using']++;
					else
						$records[$i]['unused']++;
					
					$records[$i]['cost'] += $item->totalprice;	
					unset($coupons[$key]);					
				}
			}							
			$j = strtotime("-1 ".$this->groupby_date, $j);	
		}		
		$this->items = $records;
		foreach ( $this->items as $item )
		{			
			array_unshift($this->data_graph, array( 'y_data' => date_i18n( "d.m.y", $item['date'] ), 'x_data' => $item['total'], 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item['date']),__('Количество', 'usam').': '.$this->currency_display( $item['total'] ) )) );
		}		
	}
	
	public function get_title_graph( ) 
	{
		return __('Количество созданных','usam');		
	}
}