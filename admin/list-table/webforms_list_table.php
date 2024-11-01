<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_webforms extends USAM_List_Table
{	
	protected $orderby = 'id';
	protected $order = 'DESC';
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}
	
	function column_title( $item )
	{			
		$this->row_actions_table( $this->item_edit( $item->id, $item->title, 'webform' ), $this->standart_row_actions( $item->id, 'webform', ['copy' => __('Копировать', 'usam')] ) );	
	}	
		
	function column_action( $item )
	{	
		$actions = usam_get_webform_actions();
		if ( isset($actions[$item->action]) )
			echo $actions[$item->action];
	}	
		   
	function get_sortable_columns()
	{
		$sortable = array(
			'title'      => array('title', false),			
			'type'       => array('type', false),			
			);
		return $sortable;
	}		
		
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',					
			'title'      => __('Название', 'usam'),
			'active'     => __('Активность', 'usam'),	
			'action'     => __('Действие', 'usam'),			
			'code'       => __('Код', 'usam'),	
			'id'         => __('ID', 'usam'),					
        );		
		$languages = usam_get_languages();
		if ( !empty($languages) )
		{
			$columns['language'] = __('Язык', 'usam');
		}
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
		$this->get_query_vars();		
		if ( empty($this->query_vars['include']) )
		{				
			$selected = $this->get_filter_value( 'language' );
			if ( $selected )
				$this->query_vars['language'] = array_map('sanitize_title', (array)$selected);
		} 		
		$webforms = new USAM_WebForms_Query( $this->query_vars );	
		$this->items = $webforms->get_results();		
			
		$total_items = $webforms->get_total();
		$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
	}
}
?>