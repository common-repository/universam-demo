<?php
/*
Name:Продажи по складам
Description:Продажи по складам
Date:11.08.2015
Author:universam
Version:1.0
*/
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_warehouse_sales_report extends USAM_Main_Report_List_Table
{
	protected $orderby = 'date';
	private $storages;	
	
	public function __construct( $args = [] ) 
	{		
		parent::__construct( $args );	
		$this->storages = usam_get_storages( array( 'active' => 'all' ) );
	}
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	public function column_export_date( $item )
	{	
		$timestamp = (int) $item['date'];		
		return date( 'd.m.Y', $timestamp );
	}
		
	function get_columns()
	{       
		$columns = array(   	
			'date'    => __('Дата', 'usam'),			
        );				
		foreach ( $this->storages as $storage )		
			$columns['storage_'.$storage->id] = $storage->title;
		$columns['no_storage'] = __('Склад не указан', 'usam');		
		$columns['del_storage'] = __('Склад не найден', 'usam');
        return $columns;
    }
	
	function prepare_items() 
	{		
		global $wpdb;
		
		$this->_column_headers = $this->get_column_info();
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $this->per_page;	
	
		$where = array( '1=1' );
		$join  = array();			
	
		if ( $this->end_date_interval )
			$where[] = "sd.date_insert<='$this->end_date_interval'";
		if ( $this->start_date_interval )
			$where[] = "sd.date_insert>='$this->start_date_interval'";			
		
		$selected = $this->get_filter_value( 'shipping' );	
		if ( $selected )
		{
			$in = implode(',', array_map('intval', (array)$selected) );		
			$where[] = "sd.method IN ($in)";
		}	
		$selected = $this->get_filter_value('status');	
		if ( $selected )
		{
			$in = implode("','",  array_map('sanitize_text_field', (array)$selected));		
			$where[] = "sd.status IN ('$in')";
		}		
		$selected = $this->get_filter_value( 'weekday' );
		if ( $selected )
		{
			$dayofweek = array();
			foreach ( (array)$selected as $day )
			{
				$dayofweek[] = $wpdb->prepare("DAYOFWEEK(sd.date_insert) = %d", sanitize_title($day) );
			}
			$where[] = " (".implode( ' OR ', $dayofweek ).")";		
		}	
		$selects = array( 'sd.id, sd.order_id, sd.status, sd.storage, t_sp.totalprice' );
		$selects[] = "sd.date_insert AS date";	
		
		$join[] = "LEFT OUTER JOIN (SELECT document_id, SUM(quantity*price) AS totalprice FROM `".USAM_TABLE_SHIPPED_PRODUCTS."` GROUP BY document_id) AS t_sp ON (t_sp.document_id=sd.id)";
							
		$selects = implode( ', ', $selects );	
		$where = implode( ' AND ', $where );
		$join = implode( ' ', $join );				
		$documents_data = $wpdb->get_results("SELECT $selects FROM ".USAM_TABLE_SHIPPED_DOCUMENTS." AS sd $join WHERE $where GROUP BY sd.id ORDER BY sd.date_insert DESC");	

		$start_period = true;			
		$records = array();	
		$i = 0;		
		foreach ( $this->storages as $storage )	
		{
			$new_records['storage_'.$storage->id] = 0;		
			$_storage[$storage->id] = 'storage_'.$storage->id;		
		}
		$new_records['no_storage'] = 0;		
		$new_records['del_storage'] = 0;		
		for ( $j = $this->time_calculation_interval_start; $j >= $this->time_calculation_interval_end; )
		{		
			$i++;				
			$records[$i] = $new_records;
			$records[$i]['date'] = $j;				
			$current_date = get_gmt_from_date(date( "Y-m-d H:i:s",$j));
			foreach ( $documents_data as $key => $item )
			{					
				if ( $current_date > $item->date )
				{					
					break;
				}
				else
				{					
					if ( empty($item->storage) )
						$records[$i]['no_storage'] += $item->totalprice;	
					elseif ( empty($_storage[$item->storage]) )		
						$records[$i]['del_storage'] += $item->totalprice;
					else						
						$records[$i][$_storage[$item->storage]] += $item->totalprice;					
					unset($documents_data[$key]);
				}				
			}		
			$j = strtotime("-1 ".$this->groupby_date, $j);			
		}			
		$this->total_items = $i;			

		if ( $this->per_page == 0 )
			$this->items = $records;
		else
			$this->items = array_slice( $records, $offset, $this->per_page);	
	
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page  ) );
	}
}