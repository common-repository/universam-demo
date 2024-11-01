<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/orders_table.php' );
class USAM_List_Table_Orders_advertising_campaign extends USAM_Table_Orders 
{			
	protected $views = false;
	protected $status = 'all';

	public function get_columns()
	{ 
		$columns = [
			'id'       => __('Номер заказа', 'usam'),		
			'status'   => __('Статус', 'usam'),							
			'shipping' => __('Доставка', 'usam'),				
			'method'   => __('Способ оплаты', 'usam')		
		];
		if ( !usam_check_type_product_sold( 'product' ) )
			unset($columns['shipping']);
		return $columns;
	}
	
	public function prepare_items() 
	{
		$columns = $this->get_columns();
		
		$this->get_query_vars();	
		if ( $this->viewing_allowed('order') )
		{
			$this->query_vars['cache_meta'] = true;
			$this->query_vars['cache_contacts'] = true;
			$this->query_vars['cache_companies'] = true;		
			$this->query_vars['cache_managers'] = true;
			$this->query_vars['cache_order_shippeds'] = true;
			$this->query_vars['cache_order_payments'] = true;
			$this->query_vars['add_fields'] = ['last_comment'];	
			$this->query_vars['meta_query'][] = ['key' => 'campaign_id', 'value' => $this->id, 'compare' => '='];
			if ( empty($this->query_vars['include']) )
			{							
				$this->get_vars_query_filter();
			}				
	//Ограничения просмотра				
			$view_group = usam_get_user_order_view_group( );
			if ( !empty($view_group) )
			{ 
				if ( !empty($view_group['type_prices']) )
					$this->query_vars['type_prices'] = $view_group['type_prices'];			
			}
			/*if ( !usam_check_current_user_role('seller') && !usam_check_current_user_role('shop_manager') && !usam_check_current_user_role('company_management') )		
			{
				$this->query_vars['manager_id'] = get_current_user_id();			
			}*/
			$query_orders = new USAM_Orders_Query( $this->query_vars );
			$this->items = $query_orders->get_results();
			$this->total_amount = $query_orders->get_total_amount();		
			if ( $this->per_page )
			{
				$total_items = $query_orders->get_total();	
				$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
			}
		}
	}	
}