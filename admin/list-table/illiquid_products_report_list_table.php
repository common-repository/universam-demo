<?php
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_illiquid_products_report extends USAM_Main_Report_List_Table
{	
    protected $orderby = "name";
	protected $order = "ASC";
	protected $prefix = '';	
	protected $period = 'last_30_day';	
	
	protected function get_filter_tablenav( ) 
	{
		return array( 'interval' => '' );
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(	
			'id'     => array('id', false),	
			'name'   => array('name', false),	
			'stock'  => array('stock', false),	
			'price'  => array('price', false),				
			);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(   		
			'id'       => __('Идентификатор', 'usam'),			
			'name'     => __('Название товара', 'usam'),	
			'stock'    => __('Остаток', 'usam'),				
			'price'    => __('Цена', 'usam'),				
        );
        return $columns;
    }
	
	public function get_number_columns_sql()
    {       
		return array('shipping', 'quantity_shipping' );
    }
		
	public function extra_tablenav( $which ) {	}
	
	function prepare_items() 
	{		
		global $wpdb;	
		
		$type_price = usam_get_manager_type_price();
		$this->get_standart_query_parent();
		
		$this->where[] = "stock.meta_value>0 AND stock.meta_key='stock'";	
		$this->where[] = "po.product_id IS NULL";
		$this->where[] = "p.post_type='usam-product'";
		$this->where[] = "p.post_status IN ('publish', 'inherit')";		
		
		$where = implode( ' AND ', $this->where );	
		switch ( $this->orderby ) 
		{		
			case 'stock':	
				$this->orderby = "stock.meta_value";
			break;
			case 'name':	
				$this->orderby = "p.post_title";
			break;
			case 'id':	
				$this->orderby = "po.product_id";
			break;			
		}		
		$sql = "SELECT SQL_CALC_FOUND_ROWS p.post_title AS name, p.ID AS id, IFNULL(stock.meta_value,0) AS stock, IFNULL(price_pm.meta_value,0) AS price
		FROM $wpdb->posts AS p
		LEFT JOIN ".USAM_TABLE_STOCK_BALANCES." AS stock ON (p.ID = stock.product_id)		
		LEFT JOIN ".USAM_TABLE_PRODUCTS_ORDER." AS po ON( po.product_id = p.ID AND po.date_insert <= '".$this->end_date_interval."' AND po.date_insert >= '".$this->start_date_interval."' )
		LEFT JOIN ".USAM_TABLE_PRODUCT_PRICE." AS price_pm ON(p.ID = price_pm.product_id AND price_pm.meta_key='price_{$type_price}')
		WHERE $where  ORDER BY {$this->orderby} {$this->order} {$this->limit}";		
		$this->items = $wpdb->get_results($sql, ARRAY_A);
		$this->total_items =  $total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );		
	
		$this->set_pagination_args( array(	'total_items' => $this->total_items, 'per_page' => $this->per_page ) );		
	}
}