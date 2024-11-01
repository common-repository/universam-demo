<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_sold extends USAM_List_Table 
{		    
	protected $prefix = 'c';	
	protected $period = 'last_30_day';
	function __construct( $args = array() )
	{	
		parent::__construct( $args );			
    }	

	public function column_order_date( $item ) 
	{
		echo date_i18n( __( get_option( 'date_format', 'Y/m/d' ) ), strtotime( $item['order_date'] ) );
	}
	
	public function column_order_id( $item ) 
	{
		echo usam_get_link_order( $item['order_id'] );	
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return array( 'interval' => '' );	
	}
				
	function get_sortable_columns() 
	{
		$sortable = array(
			'id'       => array('id', false),			
			'name'     => array('name', false),
			'sku'      => array('meta_value', false),	
			'quantity' => array('quantity', false),	
			'price'    => array('price', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
		//	'cb'          => '<input type="checkbox" />',		
			'name'        => __('Название', 'usam'),					
			'sku'         => __('Артикул', 'usam'),			
			'price'       => __('Цена', 'usam'),	
			'quantity'    => __('Количество', 'usam'),	
        );
        return $columns;
    }
	
	public function prepare_items() 
	{
		global $wpdb;

		if ( isset($_REQUEST['orderby']) )
			switch ( $_REQUEST['orderby'] ) 
			{		
				case 'meta_value' :
					$this->prefix = 'pm';
				break;				
			}
		$this->get_standart_query_parent( );
		
		$page = $this->get_pagenum();
		$offset = ( $page - 1 ) * $this->per_page;
		$joins = array();	

		if ( isset($_REQUEST['post'] ) )
		{
			$post_ids = array_map('intval', $_REQUEST['post']);
			$this->where[] = 'c.id IN (' . implode( ', ', $post_ids ) . ')';
		}
		$i = 1;
		//название колонок, данные которых нужно получить
		$selects = array( 'c.id', 'c.product_id', 'c.name', 'c.order_id AS order_id', 'c.price', 'SUM(c.quantity) AS quantity', 'pm.meta_value AS sku', );
		$joins[] = "INNER JOIN ".USAM_TABLE_ORDERS." AS p ON c.order_id = p.id";	
		$joins[] = "INNER JOIN ".USAM_TABLE_SHIPPED_DOCUMENTS." AS sd ON c.order_id = sd.order_id";	
		$joins[] = "LEFT OUTER JOIN ".USAM_TABLE_PRODUCT_META." AS pm ON c.product_id = pm.product_id AND pm.meta_key='sku'";		
	
		if ( $this->search != '' )
		{		
			$search_sql = array();				
			if ( is_numeric( $s ) )
				$search_sql[] = 'c.order_id='.esc_sql( $s );		
			
			$product_id = usam_get_product_id_by_sku( $s );
			if ( $product_id > 0 )
				$search_sql[] = "c.product_id=$product_id";				
			
			$search_sql[] = "c.name LIKE LOWER ('%" . esc_sql( $s ). "%')";			
			$search_terms = explode( ' ', $s );		
			foreach ( $search_terms as $term )
			{						
				$search_sql[] = "c.name LIKE LOWER ('%" . esc_sql( $term ). "%')";
			}
			$search_sql = implode( ' AND ', array_values( $search_sql ) );
			if ( $search_sql )
			{
				$this->where[] = $search_sql;
			}
		}			
		// фильтр статусов
		$selected = $this->get_filter_value( 'status' );
		if ( $selected ) 
		{						
			$selected = implode( "','",  array_map('sanitize_title', (array)$selected) );
			$this->where[] = "p.status IN ('".$selected."')";			
		}		
		$selected = $this->get_filter_value( 'shipping' );
		if ( !empty($selected) && $selected != 'all' )
		{
			$method = implode( ',',  array_map('intval', (array)$selected) );		
			$this->where[] = "sd.method IN (".$method.")";		
		}								
		$conditions = $this->get_digital_interval_for_query( array( 'product_price','product_quantity','order_price','order_quantity' ) );			
		if ( !empty($conditions) ) 
		{		
			foreach ( $conditions as $condition )
			{					
				$select = '';
				switch ( $condition['key'] )
				{
					case 'product_price' :
						$select = "c.price";					
					break;
					case 'product_quantity' :
						$select = "c.quantity";					
					break;				
					case 'order_price' :
						$select = "p.totalprice";				
					break;		
					case 'order_quantity' :
						$select = "( SELECT COUNT(*) FROM ".USAM_TABLE_PRODUCTS_ORDER." AS c WHERE c.order_id = p.id )";				
					break;	
				}
				if ( $select == '' )
					continue;			
				
				if ( ! in_array( $condition['compare'], array(
					'=', '!=', '>', '>=', '<', '<=',
					'LIKE', 'NOT LIKE',
					'IN', 'NOT IN',
					'BETWEEN', 'NOT BETWEEN',
					'EXISTS', 'NOT EXISTS',
					'REGEXP', 'NOT REGEXP', 'RLIKE'
				) ) ) 
				{
					$condition['compare'] = '=';
				}				
				$compare = $condition['compare'];	
				switch ( $condition['compare'] ) 
				{					
					case 'LIKE' :					
					case 'NOT LIKE' :
						$compare = "LIKE";	
						$value = "('%".$condition['value']."%')";						
					break;	
					case 'IN' :					
					case 'NOT IN' :
						$value = "('".implode("','",$condition['value'])."')";
					break;
					default:						
						$value = "'".$condition['value']."'";						
					break;					
				}							
				$this->where[] = "( $select {$compare}{$value})";
			}			
		}				
		$selected = $this->get_filter_value( 'storage' );
		if ( $selected && $selected != 'all' )
		{
			$storage = array_map('intval', (array)$selected);
			$in_storage = implode( ',',  $storage );		
			$this->where[] = "sd.storage IN (".$in_storage.")";		
		}		
		if ( $this->start_date_interval )
			$this->where[] = "c.date_insert>='".date('Y-m-d H:i:s', strtotime($this->start_date_interval))."' AND c.date_insert<='".date('Y-m-d H:i:s', strtotime($this->end_date_interval))."'";	
		$selected = $this->get_filter_value( 'user_role' );
		if ( !empty($selected) )
		{
			$this->where[] = "p.user_role = '".sanitize_title($selected)."'";	
		}		
		$selects = implode( ', ', $selects );
		$joins = implode( ' ', $joins );
		$where = implode( ' AND ', $this->where );	
				
		$sql = "SELECT SQL_CALC_FOUND_ROWS {$selects} FROM ".USAM_TABLE_PRODUCTS_ORDER." AS c {$joins} WHERE {$where} GROUP BY product_id ORDER BY {$this->orderby} {$this->order} {$this->limit}";	
		$this->items = $wpdb->get_results( $sql, ARRAY_A );		
		if ( $this->per_page )
		{
			$total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );				
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}	
}