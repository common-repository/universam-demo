<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_products_report extends USAM_Main_Report_List_Table
{	
    protected $orderby = "name";
	protected $order = "ASC";
	protected $prefix = '';	
	protected $period = 'last_30_day';	
	public function column_name( $item ) 
	{	
		echo "<a href ='".add_query_arg(['table' => 'product_report', 'product' => $item['id']], $this->url )."' >".$item['name']."</a>";
	}
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => '', 'status' => 'closed'];
	}
	
	public function return_post()
	{
		return ['group_id'];
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(
			'quantity' => array('quantity', false),	
			'total'    => array('total', false),		
			'id'    => array('id', false),	
			'name'    => array('name', false),	
			'min'    => array('min', false),	
			'max'    => array('max', false),						
			'views'    => array('views', false),			
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(   		
			'sku'      => __('Артикул', 'usam'),			
			'name'     => __('Название товара', 'usam'),			
			'quantity' => __('Количество', 'usam'),			
			'total'    => __('Сумма', 'usam'),	
			'pr_total' => __('Продажи %', 'usam'),
			'group'    => __('Группа', 'usam'),
			'min'      => __('Минимальная цена', 'usam'),
			'max'      => __('Максимальная цена', 'usam'),				
			'views'    => __('Просмотры', 'usam'),				
        );
        return $columns;
    }
	
	public function extra_tablenav( $which ) 
	{	
		if ( 'top' == $which )
		{				
			$this->standart_button();	
		}
	}
	
	function prepare_items() 
	{		
		global $wpdb;
	
		$this->get_standart_query_parent();
				
		$where = [];
		$join = [];
		
		$pv_where = ["post_id!=0"];		
		$pl_where = [];
		$order_status = $this->get_filter_value( 'status' );
		if ( $order_status )
		{ 
			$selected = array_map('sanitize_title', (array)$order_status);
			$pl_where[] = "p.status IN ('".implode( "','", $selected )."')";
		}	
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected ) 
			$where[] = "p.manager_id IN (".implode(",",array_map('intval', (array)$selected)).")";	
		
		$distinct = false;
		$selected = $this->get_filter_value( 'discount' );
		if ( $selected ) 
		{
			$join[] = " INNER JOIN ".USAM_TABLE_PRODUCT_DISCOUNT_RELATIONSHIPS." AS dr ON (po.product_id = dr.product_id)";
			$where[] = "dr.discount_id IN (".implode(",",array_map('intval', (array)$selected)).")";
			$distinct = true;
		}			
		
		$selected = $this->get_filter_value( 'weekday' );
		if ( !empty($selected) )
		{
			$selected = is_array($selected)?$selected:array($selected);
			$dayofweek = [];
			foreach ( $selected as $day )
			{
				$dayofweek[] = $wpdb->prepare("DAYOFWEEK(p.{$date_colum}) = %d", sanitize_title($day) );
			}
			$where[] = " (".implode( ' OR ', $dayofweek ).")";		
		}			
		if ( $this->end_date_interval )
		{
			$pl_where[] = "p.date_insert <= '$this->end_date_interval'";
			$pv_where[] = "date_insert <= '$this->end_date_interval'";
		}
		if ( $this->start_date_interval )
		{
			$pl_where[] = "p.date_insert >= '$this->start_date_interval'";		
			$pv_where[] = "date_insert >= '$this->start_date_interval'";		
		}		
		$this->where[] = "po.date_insert <= '$this->end_date_interval'";
		$this->where[] = "po.date_insert >= '$this->start_date_interval'";
		
		if ( $this->search != '' )
			$this->where[] = "po.name LIKE LOWER ('%".$this->search."%')";			
		
		$where = implode( ' AND ', $this->where );	
		switch ( $this->orderby ) 
		{					
			case 'quantity':	
				$this->orderby = 'SUM(po.quantity)';
			break;
			case 'total':	
				$this->orderby = 'SUM(po.quantity)*price';
			break;			
			case 'min':	
				$this->orderby = 'MIN(price)';
			break;
			case 'max':	
				$this->orderby = 'MAX(price)';
			break;
			case 'views':	
				$this->orderby = "pv.views";
			break;
			case 'id':	
				$this->orderby = "po.product_id";
			break;			
		}		
		$select = "pm.meta_value AS sku, po.name, MIN(price) AS min, MAX(price) AS max, SUM(po.quantity)*price AS total, SUM(po.quantity) AS quantity, po.product_id AS id, pv.views AS views";
		if( $distinct )
			$select = "DISTINCT ".$select;
		$sql = "SELECT SQL_CALC_FOUND_ROWS $select 
		FROM ".USAM_TABLE_PRODUCTS_ORDER." AS po 
		INNER JOIN ".USAM_TABLE_ORDERS." AS p ON (po.order_id=p.id AND ".implode(' AND ', $pl_where )." )
		INNER JOIN ".USAM_TABLE_PRODUCT_META." AS pm ON (pm.product_id=po.product_id AND meta_key='sku')	
		LEFT JOIN ( SELECT post_id, COUNT(*) AS views FROM ".USAM_TABLE_PAGE_VIEWED." WHERE ".implode(' AND ', $pv_where )." GROUP BY post_id ) AS pv ON (po.product_id = pv.post_id)
		".implode(' AND ', $join )."
		WHERE $where GROUP BY po.product_id ORDER BY {$this->orderby} {$this->order}";			
		$this->items = $wpdb->get_results($sql, ARRAY_A);	
		$this->total_items =  $total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );		
		foreach ( $this->items as $item )
		{		
			foreach ( $item as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = 0;
				switch ( $key ) 
				{
					case 'total' :
					case 'views' :
					case 'quantity' :	
						$this->results_line[$key] += (float)$value;			
					break;							
					default:			
						$this->results_line[$key] = '';
					break;			
				}				
			}
		}	
		if ( !empty($this->results_line['total']) )
		{
			$items = $this->items;		
			$comparison = new USAM_Comparison_Object( 'total', 'DESC' );
			usort( $items, array( $comparison, 'compare' ) );
			
			$A80 = round($this->results_line['total']*80/100,2);
			$B15 = round($this->results_line['total']*15/100,2);	
			$A = 0;
			$B = 0;		
			foreach ( $items as $key => $item )
			{
				if ( $A+$item['total'] <= $A80 )
				{
					$A = $A+$item['total'];
					$items[$key]['group'] = 'A';
				}
				elseif ( $B+$item['total'] <= $B15 )
				{
					$A = $A80;
					$B = $B+$item['total'];
					$items[$key]['group'] = 'B';
				}
				else
				{
					$A = $A80;
					$B = $B15;
					$items[$key]['group'] = 'C';
				}
			}
			foreach ( $this->items as $key => $item )
			{	
				if ( $item['total'] )
				{
					$this->items[$key]['pr_total'] = round($item['total']*100/$this->results_line['total'],2);
				}
				foreach ( $items as $key2 => $item2 )
				{
					if ( $item['total'] == $item2['total'] )
					{
						$this->items[$key]['group'] = $item2['group'];
						unset($items[$key2]);
						break;
					}
				}
			}		
		}				
	}
}