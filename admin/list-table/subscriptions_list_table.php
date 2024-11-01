<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/document/subscriptions_query.class.php' );
require_once( USAM_FILE_PATH . '/includes/document/subscription.class.php'  );
class USAM_List_Table_subscriptions extends USAM_List_Table
{	
	protected $orderby = 'id';
	protected $order   = 'asc'; 	
	function get_bulk_actions_display() 
	{
		$actions = [];
		if ( current_user_can('delete_subscription') )
			$actions['delete'] = __('Удалить', 'usam');
		return $actions;
	}
	
	function column_id( $item )
	{	
		$title = $this->item_view( $item->id, $item->id, 'subscription' );
		$title .= "<strong class='item_status status_blocked'>".usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price])."</strong> <small class='document_number_products'>".sprintf( _n('1 товар', '%s товаров', $item->number_products, 'usam'), number_format_i18n( $item->number_products ) ).'</small>';
		$title .= '<div class="document_date">'.__("от","usam").' '.usam_local_formatted_date( $item->date_insert ).'</div>';	
			
		$actions = $this->standart_row_actions( $item->id, 'subscription' );	
		if ( !current_user_can('edit_subscription') )
			unset($actions['edit']);
		if ( !current_user_can('delete_subscription') )
			unset($actions['delete']);		
		$this->row_actions_table( $title, $actions );
	}
	
	function column_status( $item )
	{	
		if ( $item->status == 'signed' )
			$class = 'item_status_valid';
		elseif ( $item->status == 'not_signed' )
			$class = 'status_blocked';
		else
			$class = 'item_status_notcomplete';
		?><div class="status_container"><div class='<?php echo $class; ?> item_status'><?php echo usam_get_status_name_subscription( $item->status ); ?></div></div><?php 
		if ( !empty($item->manager_id) )
		{
			$url = add_query_arg(['manager' => $item->manager_id, 'page' => $this->page, 'tab' => $this->tab], wp_get_referer() );	
			?><strong><?php _e('Ответственный','usam'); ?>:</strong> <a href="<?php echo $url; ?>"><?php echo stripcslashes(usam_get_manager_name( $item->manager_id )); ?></a><?php	
		}
	}	
		
	function column_product( $item )
	{	
		$products = usam_get_products_subscription( $item->id );
		$count = count($products);
		$i = 0;
		foreach ( $products as $product )
		{	
			$i++;
			if ( $i > 1 )
				echo '<hr size="1" width="90%">';
			echo "<div>$product->name</div>";			
			if ( $i == 3 && $count > $i )
			{
				echo '<hr size="1" width="90%">';
				$n = $count - $i;
				echo "<p>".sprintf( _n( 'Есть еще %s товара.','Есть еще %s товаров.', $n, 'usam'), $n )."</p>";
				break;
			}
		}
	}	
	  
	function get_sortable_columns() 
	{
		$sortable = [
			'id'           => array('id', false),		
			'counterparty' => array('counterparty', false),		
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'           => '<input type="checkbox" />',
			'id'           => __('ID', 'usam'),
			'status'       => __('Статус', 'usam'),		
			'interval'     => __('Дата подписки', 'usam'),				
			'counterparty' => __('Подписчик', 'usam'),					
			'product'      => __('Товары / Услуги', 'usam'),
        );
        return $columns;
    }
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		$this->query_vars['cache_products'] = true;
		if ( empty($this->query_vars['include']) )
		{			
					
		} 
		$subscriptions = new USAM_Subscriptions_Query( $this->query_vars );
		$this->items = $subscriptions->get_results();		
		$this->total_items = $subscriptions->get_total();
		$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
	}
}