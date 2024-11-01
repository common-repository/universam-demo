<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_all_documents_list_table.class.php' );
require_once( USAM_FILE_PATH .'/includes/document/leads_query.class.php' );
class USAM_List_Table_leads extends USAM_Table_ALL_Documents 
{	
	public function __construct( $args = [] ) 
	{			
		parent::__construct( $args );		
				
		$this->statuses = usam_get_object_statuses(['type' => 'lead', 'fields' => 'code=>data', 'cache_results' => true]);		
		add_action( 'admin_footer', [&$this, 'admin_footer'] );			
    }
	
	public function get_bulk_actions() 
	{
		if ( ! $this->bulk_actions )
			return [];

		$actions = [];
		if ( current_user_can('delete_lead') )			
			$actions['delete'] = __('Удалить', 'usam');
		if ( current_user_can('edit_lead') )
			$actions['manager'] = __('Менеджер', 'usam');			
		return $actions;
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];		
	}
	
	public function get_columns()
	{
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'name'      => __('Название', 'usam'),			
			'status'   => __('Статус', 'usam'),
			'customer' => __('Клиент', 'usam'),
			'date'     => __('Дата', 'usam'),		
			'last_comment'  => __('Последний комментарий', 'usam'),			
			'manager'   => __('Менеджер', 'usam'),			
		];
		if ( !current_user_can('edit_lead') && !current_user_can('delete_lead') )
			unset($columns['cb']);		
		return $columns;
	}
	
	public function get_vars_query_filter() 
	{			
		$selected = $this->get_filter_value( 'seller' );
		if ( $selected )
			$this->query_vars['bank_account_id'] = array_map('intval', (array)$selected);	
					
		if ( $this->status != 'all' ) 
		{			
			if ( $this->status == 'all_in_work' )
			{
				$this->query_vars['status'] = [];				
				foreach ( $this->statuses as $key => $status )	
				{
					if ( !$status->close )
						$this->query_vars['status'][] = $status->internalname;
				}
			}
			else			
				$this->query_vars['status'] = $this->status;
		}
		else
			$this->query_vars['status__not_in'] = ['', 'delete'];
		
		$selected = $this->get_filter_value( 'code_price' );
		if ( $selected )
			$this->query_vars['type_prices'] = array_map('sanitize_title', (array)$selected);		
		
		$selected = $this->get_filter_value( 'payer' );
		if ( $selected )
			$this->query_vars['type_payer'] = array_map('intval', (array)$selected);
			
		$selected = $this->get_filter_value( 'category' );
		if ( $selected ) 
			$this->query_vars['categories'] = array_map('intval', (array)$selected);
			
		$selected = $this->get_filter_value( 'brands' );
		if ( $selected ) 
			$this->query_vars['brands'] = array_map('intval', (array)$selected);					
					
		$selected = $this->get_filter_value( 'document_discount' );
		if ( $selected )
			$this->query_vars['document_discount'] = array_map('intval', (array)$selected);		
					
		$selected = $this->get_filter_value('contacts');
		if ( $selected )
			$this->query_vars['contacts'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value('companies');
		if ( $selected )
			$this->query_vars['companies'] = array_map('intval', (array)$selected);	
		$selected = $this->get_filter_value( 'users' );
		if ( $selected )
		{
			if ( is_numeric($selected) )			
				$this->query_vars['user_id'] = absint($selected);
			else								
				$this->query_vars['user_login'] = sanitize_title($selected);				
		}
		$this->get_digital_interval_for_query(['sum', 'prod', 'tax']);
	}
	
	public function prepare_items() 
	{
		$columns = $this->get_columns();
		
		$this->get_query_vars();
		if ( $this->viewing_allowed('lead') )
		{
			$this->query_vars['cache_meta'] = true;
			$this->query_vars['cache_contacts'] = true;
			$this->query_vars['cache_companies'] = true;		
			$this->query_vars['cache_managers'] = true;
			$this->query_vars['fields'] = ['last_comment','all'];				
			if ( isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'companies' )
				$this->query_vars['companies'] = $this->id;		
			else
				$this->query_vars['contacts'] = $this->id;	
			$this->query_vars['meta_query'] = array();
			if ( empty($this->query_vars['include']) )
			{							
				$this->get_vars_query_filter();
			}		
			$query = new USAM_Leads_Query( $this->query_vars );	
			$this->items = $query->get_results();		
			$this->total_amount = $query->get_total_amount();		
			if ( $this->per_page )
			{
				$total_items = $query->get_total();	
				$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
			}
		}
	}	
	
	function column_status( $item ) 
	{		
		usam_display_status( $item->status, 'lead' );
	}
	
	function column_name( $item )
	{		
		if ( $item->name == '' )
			$name = "<a href='".esc_url( add_query_arg(['form' => 'view', 'form_name' => 'lead', 'id' => $item->id], $this->url))."'>".usam_get_document_name('lead')." № {$item->id}</a>";	
		else
			$name = "<a href='".esc_url( add_query_arg(['form' => 'view', 'form_name' => 'lead', 'id' => $item->id], $this->url) )."'>{$item->name}</a>";
		$name .= "<br><strong class='item_status status_blocked'>".usam_get_formatted_price($item->totalprice, ['type_price' => $item->type_price]).'</strong>';	
		$this->row_actions_table( $name, $this->get_row_actions( $item ) );	
	}	
		
	protected function get_row_actions( $item ) 
    { 		
		$actions = $this->standart_row_actions( $item->id, 'lead' );
		if ( !current_user_can('delete_lead'))
			unset($actions['delete']);			
		if ( !current_user_can('edit_lead') )
			unset($actions['edit']);		
		return $actions;
	}		
}