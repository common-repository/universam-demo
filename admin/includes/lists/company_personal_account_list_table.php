<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/company_table.php' );
class USAM_Company_Personal_Account_Table extends USAM_Companies_Table
{		
	protected $order = 'DESC';	
	function no_items() 
	{
		if ( $this->search != '' )
			_e( 'Ничего не найдено', 'usam');
		else			
			_e( 'Ни для одной компании не создан личный кабинет', 'usam');
	}
			
	function column_company( $item ) 
    {
		echo '<span class="js-object-value">'.$item->name.'</span>';
	}	
	
	function column_select( $item )
    {		
		echo "<a href='' id='add_user' data-id='$item->user_id'>".__('Выбрать','usam')."</a>";
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr id = "company-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	function get_columns()
	{		
        $columns = array(           		
			'company'      => __('Компания', 'usam'),	
			'user_login'  => __('Логин', 'usam'),			
			'type'        => __('Тип компании', 'usam'),	
			'select'      => '',				
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{		
		$this->get_query_vars();
		$this->query_vars['cache_case'] = true;
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['cache_meta'] = true;
		$this->query_vars['open'] = 1;			
		$this->query_vars['accounts'] = true;
		if ( $this->search == '' )
		{						
			$this->get_vars_query_filter();
		} 
		$_contacts = new USAM_Companies_Query( $this->query_vars );
		$this->items = $_contacts->get_results();		
		$this->total_items = $_contacts->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>