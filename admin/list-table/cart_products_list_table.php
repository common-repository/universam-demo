<?php
require_once( USAM_FILE_PATH . '/includes/basket/products_basket_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_cart_products extends USAM_List_Table
{		
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}
	
	function column_product_title( $item )
	{	
		$lzy = defined( 'DOING_AJAX' ) && DOING_AJAX ? false : true; 							
		echo "<span class='js-product-viewer-open viewer_open product_image image_container' product_id='$item->product_id'>".usam_get_product_thumbnail( $item->product_id, 'manage-products', '', $lzy )."</span>";
		echo "<a href='".get_edit_post_link( $item->product_id )."' class='product_title_link'>".$item->name."</a>"; 
		$sku = usam_get_product_meta( $item->product_id, 'sku' );
		if ( $sku )
		{
			?><div class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span id="product_sku-<?php echo $item->product_id; ?>" class="js-copy-clipboard"><?php echo esc_html( $sku ) ?></span></div><?php
		}
	}
	
	function column_price( $item )
	{	
		echo usam_get_formatted_price( $item->price );
	}
	
	function column_quantity( $item )
	{	
		echo usam_currency_display( $item->quantity, ['currency_symbol' => false, 'decimal_point' => false, 'currency_code' => false] );
	}
				
	function get_sortable_columns() 
	{
		$sortable = array(
			'id'       => array('id', false),
			'product_title'     => array('name', false),
			'price'     => array('price', false),
			'date'     => array('date', false),	
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [
			'cb'         => '<input type="checkbox" />',
			'product_title' => __('Название товара', 'usam'),	
			'price'      => __('Цена', 'usam'),	
			'quantity'   => __('Количество', 'usam'),				
			'date'       => __('Дата', 'usam'),				
        ];				
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		$this->query_vars['cart_id'] = $this->id;	
		if ( empty($this->query_vars['include']) )
		{			
			$selected = $this->get_filter_value( 'banner_location' );
			if ( $selected )
				$this->query_vars['banner_location'] = array_map('sanitize_title', $selected);	
			
			$selected = $this->get_filter_value( 'language' );
			if ( $selected )
				$this->query_vars['language'] = array_map('sanitize_title', (array)$selected);
		} 
		$query = new USAM_Products_Basket_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}