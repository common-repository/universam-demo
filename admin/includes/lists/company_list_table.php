<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/company_table.php' );
class USAM_Company_Table extends USAM_Companies_Table
{		
	protected $order = 'DESC';
		
	function column_company( $item ) 
    {
		echo '<span class="js-object-value">'.$item->name.'</span>';
	}	
	
	function column_communication( $item ) 
	{
		$email = usam_get_company_metadata( $item->id, 'email' );			
		if ( !empty($email) )
		{			
			echo "<div class = 'js-select-email select_communication'>".$email."</div>";
		}
	}
	
	function column_select( $item ) 
    {		
		echo "<a href=''>".__('добавить', 'usam')."</a>";
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
			'company'        => __('Компания', 'usam'),	
			'communication'  => __('Адрес электронной почты', 'usam'),			
			'type'           => __('Тип компании', 'usam'),			
        );		
		if ( empty($_GET['screen']) || $_GET['screen'] != 'address_book' )
			 $columns['select'] =  __('Выбрать', 'usam');
        return $columns;
    }
	
	
	function prepare_items() 
	{		
		$this->get_query_vars();
		$this->query_vars['cache_case'] = true;
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['cache_meta'] = true;
		$this->query_vars['open'] = 1;			
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