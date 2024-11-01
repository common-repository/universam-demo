<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_companies_communication_errors extends USAM_List_Table 
{	
	protected $communication_type = 'email';	
	function get_bulk_actions_display() 
	{			
		$actions = array(
			'delete'    => __('Удалить', 'usam'),
			'activate'  => __('Активировать', 'usam')
		);
		return $actions;
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return array( 'interval' => '' );			
	}	
	
	function column_communication( $item )
	{	
		$actions = array(			
			'delete' => '<a class="usam-delete-link" href="'.$this->get_nonce_url( add_query_arg( array('action' => 'delete', 'cb' => $item->id), $this->url ) ).'">'.__('Удалить', 'usam').'</a>'
		);
		if ( $item->status == 0 )		
			$actions['activate'] = '<a class="usam-activate-link" href="'.$this->get_nonce_url( add_query_arg( array('action' => 'change_status', 'status' => 2, 'id' => $item->id), $this->url ) ).'">'.__('Разблокировать', 'usam').'</a>';
		
		$this->row_actions_table( $item->communication, $actions );
	}
	
	function column_company( $item )
	{ 
		$company = usam_get_company( $item->company_id );	
		if ( !empty($company) )
		{					
			echo "<a href='".usam_get_company_url( $item->company_id )."'>".$company['name']. "</a>";
		}
	}
	
	function column_active( $item )
	{	
		echo $item->status?__("Разблокирована","usam"):__("Отправка не возможна","usam");
	}
	
	function column_reason( $item )
	{	
		echo usam_get_text_communication_error( 'email', $item->reason );
	}
	
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox" />',		
			'communication' => __('Почта', 'usam'),
			'company'       => __('Компания', 'usam'),	
			'active'        => __('Статус отправки', 'usam'),	
			'reason'        => __('Причина', 'usam'),		
			'date'          => __('Дата', 'usam'),				
        );		
        return $columns;	
    }	

	function prepare_items() 
	{	
		$this->get_query_vars();	
		
		$this->query_vars['communication_type'] = $this->communication_type;	
		$this->query_vars['fields'] = array('all','company');		
		if ( empty($this->query_vars['include']) )
		{
			$selected = $this->get_filter_value( 'reason' );
			if ( $selected )
				$this->query_vars['reason'] = array_map('sanitize_title', (array)$selected);		
			
			$selected = $this->get_filter_value( 'status' );
			if ( $selected )
				$this->query_vars['status'] = array_map('sanitize_title', (array)$selected);
		} 				
		$query = new USAM_Communication_Errors_Query( $this->query_vars );
		$this->items = $query->get_results();
		$ids = array();
		foreach ( $this->items as $item )
		{			
			$ids[] = $item->company_id;
		}
		usam_get_companies(['include' => $ids]);
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}	
}