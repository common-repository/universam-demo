<?php
require_once(USAM_FILE_PATH.'/includes/marketplace/sellers_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_sellers extends USAM_List_Table 
{  
	function get_bulk_actions_display() 
	{			
		$actions = [
			'delete'    => __('Удалить', 'usam'),
		];
		return $actions;
	}	
	
	function column_name( $item ) 
    {		
		$this->row_actions_table( $this->item_view($item->id, $item->name, 'seller'), $this->standart_row_actions( $item->id, 'seller' ) );	
    }
	
	function column_status( $item )
	{
		usam_display_status( $item->status, $item->seller_type );
	}
		
	public static function column_featured( $item )
	{		
		if ( usam_checks_seller_from_customer_list( 'sticky', $item->id ) ) : ?>
			<span class="usam-dashicons-icon list_selected js-sticky-seller-toggle" seller="<?php echo $item->id; ?>"></span>					
		<?php else: ?>
			<span class="usam-dashicons-icon js-sticky-seller-toggle" title="<?php _e( 'Добавить в список', 'usam'); ?>" seller="<?php echo $item->id; ?>"></span>					
		<?php endif;
	}	
	
	function column_rating( $item ) 
    {		
		echo usam_get_rating( $item->rating );
    }
	
	function get_sortable_columns()
	{
		$sortable = array(
			'name'              => array('name', false),
			'status'            => array('status', false),
			'date'              => array('date_insert', false),		
			'rating'            => array('rating', false),				
			);
		return $sortable;
	}

	function get_columns()
	{
        $columns = [ 
			'cb'      => '<input type="checkbox" />',			
			'name'    => __('Название продавца', 'usam'),				
			'rating'  => __('Рейтинг', 'usam'),		
			'number_products'  => __('Товаров', 'usam'),		
		//	'status'  => __('Статус', 'usam'),		
			'featured'  => '',	
			'date'    => __('Дата', 'usam'),			
        ]; 
        return $columns;
    }
	
	function prepare_items() 
	{		
		$this->get_query_vars();
		$this->query_vars['cache_thumbnail'] = true;		
		$this->query_vars['cache_results'] = true;
		if ( empty($this->query_vars['include']) )
		{				
			$this->get_digital_interval_for_query(['number_products']);
		} 		
		$query = new USAM_Sellers_Query( $this->query_vars );
		$this->items = $query->get_results();		
		$this->total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}