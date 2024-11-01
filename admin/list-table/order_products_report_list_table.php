<?php
require_once(USAM_FILE_PATH.'/includes/document/products_order_query.class.php');
include( USAM_FILE_PATH . '/admin/includes/list_table/main_report_list_table.php' );
class USAM_List_Table_order_products_report extends USAM_Main_Report_List_Table
{	
	public  $orderby = 'date';	
	public  $order   = 'DESC';	
	protected $period = 'last_30_day';	

	public function column_name( $item ) 
	{		
		if ( $item['type'] == 'category' )
			echo "<strong>".$item['name']."</strong>";
		else
			echo $item['name'];
	}
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => ''];
	}	
	
	function no_items() 
	{
		_e( 'Заказы не найдены', 'usam');
	}	
	
	function get_columns()
	{	
		$columns = array(  		
			'name'              => __('Номенклатура', 'usam'),
			'sku'               => __('Артикул', 'usam'),
			'stock'             => __('Остаток', 'usam'),
			'quantity'          => __('Количество', 'usam'),
			'price'             => __('Цена продажи', 'usam'),
			'totalprice'        => __('Сумма', 'usam'),						
        );	
		return $columns;
    }
		
	function prepare_items() 
	{			
		$this->get_query_vars();	
		$selected = $this->get_filter_value( 'status' );		
		if ( $selected )
			$this->query_vars['order_status'] = array_map('sanitize_title', (array)$selected);
		
		$selected = $this->get_filter_value( 'seller' );
		if ( $selected )
			$this->query_vars['bank_account_id'] = array_map('intval', (array)$selected);
		
		$selected = $this->get_filter_value( 'manager' );
		if ( $selected )
			$this->query_vars['order_manager'] = array_map('intval', (array)$selected);
		
		$selected = $this->get_filter_value( 'paid' );
		if ( $selected )
			$this->query_vars['order_paid'] = array_map('intval', (array)$selected);
		
		$selected = $this->get_filter_value( 'code_price' );
		if ( $selected )
			$this->query_vars['order_type_price'] = array_map('intval', (array)$selected);
		
		$selected = $this->get_filter_value( 'payer' );
		if ( $selected )
			$this->query_vars['order_payer'] = array_map('intval', (array)$selected);

		$products = usam_get_products_order_query( $this->query_vars );		
		$product_category_ids = array();
		$ids = [];
		foreach ( $products as $key => $product )
		{
			$ids[] = $product->product_id;
		}		
		update_object_term_cache($ids, 'usam-category');
		foreach ( $products as $key => $product )
		{
			$terms = get_the_terms( $product->product_id, 'usam-category' );	
			if ( !empty($terms) )
				foreach ( $terms as $term )
				{
					if ( isset($product_category_ids[$term->term_id][$product->product_id]) )
						$product_category_ids[$term->term_id][$product->product_id]->quantity += $product->quantity;		
					else
						$product_category_ids[$term->term_id][$product->product_id] = $product;	
				}
			unset($products[$key]);
		} 
		if ( !empty($product_category_ids) )
		{
			$this->results_line['stock'] = 0;
			$this->results_line['quantity'] = 0;		
			$this->results_line['totalprice'] = 0;
			
			$terms = get_terms(['taxonomy' => 'usam-category', 'include' => array_keys($product_category_ids), 'update_term_meta_cache' => false]);
			foreach ( $terms as $term )
			{						
				$category_quantity = 0;
				$category_stock = 0;
				$category_totalprice = 0;
				foreach ( $product_category_ids[$term->term_id] as $product_id => $product )
				{
					$stock = usam_get_product_stock($product_id, 'stock');
					$category_quantity += $product->quantity;
					$category_stock += $stock;
					$category_totalprice += $product->price*$product->quantity;										
				}		
				$this->items[] = array('name' => $term->name, 'sku' => '', 'stock' => $category_stock, 'quantity' => $category_quantity, 'price' => '', 'totalprice' => $category_totalprice, 'type' => 'category');			
				foreach ( $product_category_ids[$term->term_id] as $product_id => $product )
				{
					$stock = usam_get_product_stock($product_id, 'stock');
					$this->results_line['stock'] += $stock;
					$this->results_line['quantity'] += $product->quantity;	
					$this->results_line['totalprice'] += $product->price*$product->quantity;	
					
					$this->items[] = array( 'name' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$product->name, 'sku' => usam_get_product_meta($product_id, 'sku'), 'stock' => $stock, 'quantity' => $product->quantity, 'price' => $product->price, 'totalprice' => $product->price*$product->quantity, 'type' => 'product' );				
				}		
			}		
		}
	}
}
?>