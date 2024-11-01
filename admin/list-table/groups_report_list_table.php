<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_groups_report extends USAM_Main_Report_List_Table
{	
    protected $orderby = "total";
	protected $order = "desc";
	protected $prefix = '';	
	protected $period = 'last_30_day';
	private $display_products = false;	
	private $results_items = array();	
	
	public function column_name( $item ) 
	{	
		$selected = $this->get_filter_value( 'date' );
		$date_to_interval =  $date_from_interval = '';
		if ( !empty($selected) )
			$values = explode('|',$selected);
		if ( !empty($values[0]) )		
			$date_from_interval = $values[0];
		if ( !empty($values[1]) )	
			$date_to_interval = $values[1];			
		$children = get_term_children($item['id'], $item['taxonomy']);
		$url = !empty($children)?add_query_arg(['table' => 'groups_report', 'group_id' => $item['id'], 'taxonomy' => $item['taxonomy'], 'date_from_interval' => $date_from_interval, 'date_to_interval' => $date_to_interval], $this->url ):add_query_arg( array('table' => 'products_report', 'group_id' => $item['id'] ), $this->url );
		echo "<a href ='$url' >".$item['name']."</a>";
	}
	
	public function column_quantity( $item ) 
	{
		echo round($item['quantity'],0);
	}
	
	public function return_post()
	{
		return ['group_id', 'taxonomy'];
	}	
	
	protected function get_filter_tablenav( ) 
	{
		return array( 'interval' => '' );
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(
			'quantity' => array('quantity', false),	
			'total'    => array('total', false),				
			'name'    => array('name', false),						
			'views'    => array('views', false),			
			'pr_total' => array('total', false),
			'group' => array('total', false),
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(   		
			'id'       => __('Идентификатор', 'usam'),			
			'name'     => __('Название', 'usam'),			
			'quantity' => __('Количество', 'usam'),			
			'total'    => __('Продажи', 'usam'),	
			'pr_total' => __('Продажи %', 'usam'),
			'group'    => __('Группа', 'usam'),								
			'views'    => __('Просмотры', 'usam'),				
        );
        return $columns;
    }
	
	public function get_number_columns_sql()
    {       
		return array('quantity', 'total', 'pr_total', 'views');
    }
		
	public function extra_tablenav( $which ) {	}
	
	function prepare_items() 
	{		
		global $wpdb;
		
		$this->where = array("1 = '1'");
		
		$join = array();
		$pv_where = array("term_id!=0");
		$pl_where = array("pl.status='closed'");		
		if ( $this->end_date_interval )
		{
			$pl_where[] = "pl.date_insert <= '$this->end_date_interval'";
			$pv_where[] = "date_insert <= '$this->end_date_interval'";
		}
		if ( $this->start_date_interval )
		{
			$pl_where[] = "pl.date_insert >= '$this->start_date_interval'";		
			$pv_where[] = "date_insert >= '$this->start_date_interval'";		
		}		
		$selected = $this->get_filter_value( 'weekday' );
		if ( $selected )
		{
			$selected = array_map('intval', (array)$selected);
			$pl_where[] = "DAYOFWEEK(pl.date_insert ) IN ('".implode("','",$selected)."')";	
			$pv_where[] = "DAYOFWEEK(date_insert ) IN ('".implode("','",$selected)."')";	
		}	
		$select = "tt.parent, terms.name, tt.taxonomy, tt.term_id AS id";	
		$taxonomy = $this->get_filter_value( 'taxonomy' );		
		if ( $taxonomy )
			$taxonomy = is_array($taxonomy)?$taxonomy:array($taxonomy);	
		else
			$taxonomy = array( "usam-brands", "usam-category_sale", 'usam-catalog', 'usam-category' );			
		$group_id = (int)$this->get_filter_value( 'group_id' );		
		
		if ( $this->search != '' )
		{
			$this->where[] = "terms.name LIKE %{$this->search}%";	
		}		
		$selected = $this->get_filter_value( 'code_price' );
		if ( $selected )
			$pl_where[] = "pl.type_price IN ('".implode("','",array_map('sanitize_title', (array)$selected))."')";						
		$select .= ",SUM(po.total) AS total, SUM(po.quantity) AS quantity, pv.views AS views";
				
		$where = implode( ' AND ', $this->where );		
		$join = implode( ' ', $join );
	
		$sql = "SELECT SQL_CALC_FOUND_ROWS $select
		FROM {$wpdb->prefix}terms AS terms
		INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON (terms.term_id = tt.term_id AND tt.taxonomy IN ('".implode( "','", $taxonomy )."')) 	
		LEFT JOIN {$wpdb->prefix}term_relationships AS tr ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
		LEFT JOIN (
			SELECT SUM(po.quantity*po.price) AS total, SUM(po.quantity) AS quantity, po.product_id FROM ".USAM_TABLE_PRODUCTS_ORDER." AS po
			INNER JOIN ".USAM_TABLE_ORDERS." AS pl ON (po.order_id=pl.id AND ".implode(' AND ', $pl_where ).")";
			
			$selected = $this->get_filter_value( 'storage' );
			if ( $selected )
			{
				$storage = array_map('intval', (array)$selected);
				$sql .= " INNER JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." ON (".USAM_TABLE_SHIPPED_DOCUMENTS.".order_id = pl.id AND ".USAM_TABLE_SHIPPED_DOCUMENTS.".storage IN (".implode( ',',  $storage )."))"; 
			}	
			
			$sql .= "GROUP BY product_id
			) po ON (po.product_id = tr.object_id)
		LEFT JOIN ( SELECT term_id, COUNT(*) AS views FROM ".USAM_TABLE_PAGE_VIEWED." WHERE ".implode(' AND ', $pv_where )." GROUP BY term_id ) AS pv ON (tt.term_id = pv.term_id)
		$join
		WHERE $where GROUP BY terms.term_id";
		$results = $wpdb->get_results($sql);
		$this->total_items = count($results);	

		foreach ( $results as $item )
		{					
			if ( $item->parent != $group_id )
				continue;
			$new = (array)$item;
			$children = get_term_children( $item->id, $item->taxonomy );
			foreach ( $results as $item2 )
			{  				
				if ( in_array($item2->id, $children) )
				{
					$children2 = get_term_children( $item2->id, $item2->taxonomy );
					
					if ( empty($children2) && $item2->total )
					{
						foreach ( array('quantity', 'total') as $column )
							$new[$column] += $item2->$column;
					}
				}
			}
			foreach ( $new as $key => $value )
			{
				if ( !isset($this->results_line[$key]) )
					$this->results_line[$key] = 0;
				switch ( $key ) 
				{		
					case 'id' :
					case 'name' :
					case 'taxonomy' :
					case 'group' :
						$this->results_line[$key] = '';
					break;			
					default:	
						$this->results_line[$key] += $value;
					break;		
				}
			}
			$this->items[] = $new;
		}			
		if ( !empty($this->results_line['total']) )
		{
			$items = $this->items;		
			$comparison = new USAM_Comparison_Object( 'total', 'DESC' );
			usort( $items, array( $comparison, 'compare' ) );
			
			$A80 = round($this->results_line['total']*80/100,2);
			$B15 = round($this->results_line['total']*15/100,2);	
			$C = $this->results_line['total'] - ($B15 + $A80);
			$A = 0;
			$B = 0;		
			foreach ( $items as $key => $item )
			{				
				if ( $item['total'] == 0 )
				{					
					$items[$key]['group'] = 'C';
				}
				elseif ( $A+$item['total'] <= $A80 || $item['total'] > $A80 )
				{
					$A = $A+$item['total'];
					$items[$key]['group'] = 'A';
				}
				elseif ( $B+$item['total'] <= $B15 || $item['total'] > $B15 )
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
		$this->forming_tables();		
	}
}