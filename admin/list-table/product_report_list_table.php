<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_Product_report extends USAM_Main_Report_List_Table
{	
    protected $groupby_date = 'month';
	protected $period = 'last_30_day';	
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	public function return_post()
	{
		return ['product'];
	}	
		
	public function column_date( $item ) 
	{	
		$timestamp = (int) $item->date;
		echo date( 'd.m.Y', $timestamp );			
	}
	
	function get_columns()
	{
        $columns = array(   			
			'date'     => __('Дата', 'usam'),			
			'quantity' => __('Количество', 'usam'),
			'total'    => __('Сумма', 'usam'),	
		//	'views'    => __('Просмотры', 'usam'),				
        );
		$storages = usam_get_storages();
		foreach ( $storages as $storage )
		{
			$columns[$storage->id] = $storage->title;
		}
        return $columns;
    }
	
	function prepare_items() 
	{		
		global $wpdb;
		
		$this->_column_headers = $this->get_column_info();	

		$records = array();	
		$product_id = (int)$this->get_filter_value( 'product' );
		if ( !empty($product_id) )
		{						
			$where = array( '1 = 1' );	
					
			$where[] = "cc.date_insert<='$this->end_date_interval'";
			$where[] = "cc.date_insert>='$this->start_date_interval'";
			$where[] = "pl.status = 'closed'";
		
			$where = implode( ' AND ', $where );		
					
			$sql = "SELECT cc.date_insert AS date, cc.quantity, cc.price, sd.storage FROM ".USAM_TABLE_ORDERS." AS pl LEFT JOIN ".USAM_TABLE_PRODUCTS_ORDER." AS cc ON cc.order_id = pl.id LEFT JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." AS sd ON sd.order_id = pl.id  WHERE cc.product_id = '$product_id' AND $where ORDER BY cc.date_insert DESC";		
			$data_report = $wpdb->get_results($sql);
			$views = [];
			$i = 0;			
			$storages = usam_get_storages();			
			for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
			{			
				$records[$i] = new stdClass();		
				$records[$i]->date = $j;
				$records[$i]->quantity = 0;
				$records[$i]->total = 0;
				$records[$i]->views = 0;	
				$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
				foreach ( $storages as $storage )
				{
					$storage_id = $storage->id;
					$records[$i]->$storage_id = 0;
				}
				foreach ( $data_report as $key => $item )
				{			
					if ( $current_date >= $item->date )
					{	
						break;
					}
					else
					{	
						$storage_id = $item->storage;
						$total = $item->price*$item->quantity;
						if ( isset($records[$i]->$storage_id) )
						{
							$records[$i]->$storage_id += $total;
						}
						$records[$i]->quantity += $item->quantity;
						$records[$i]->total += $total;					
						unset($data_report[$key]);					
					}
				}					
				$i++;
				$j = strtotime("-1 ".$this->groupby_date, $j);		
			}
		}	
		$this->items = $records;	
		foreach ( $this->items as $item )
		{		
			array_unshift($this->data_graph, ['y_data' => date_i18n( "d.m.y", $item->date ), 'x_data' => $item->total, 'label' => array( __('Дата', 'usam').': '.date_i18n("d.m.y", $item->date),__('Количество', 'usam').': '.$this->currency_display( $item->total ) )] );
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