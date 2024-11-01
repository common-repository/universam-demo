<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Document_Status_Table extends USAM_List_Table
{	   	
	protected $orderby = 'sort';
	protected $order = 'ASC';	
	function __construct( $args = array() )
	{			
		parent::__construct( $args );		
		USAM_Admin_Assets::sort_fields( 'status' );
    }
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}
	
	function column_name( $item )
	{	
		$name = $this->item_edit( $item->id, $item->name, $item->type.'_status' );
		$this->row_actions_table( $name, $this->standart_row_actions( $item->id, $item->type.'_status' ) );
	}
				   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'           => array('name', false),	
			'active'         => array('active', false),			
			'internalname'   => array('internalname', false),		
			'sort'           => array('sort', false),			
			); 
		return $sortable;
	}
	
	protected function get_default_primary_column_name() 
	{
		return 'name';
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',					
			'name'             => __('Название', 'usam'),				
			'active'           => __('Активность', 'usam'),
			'internalname'     => __('Код', 'usam'),				
			'description'      => __('Описание', 'usam'),
			'color'            => '',				
			'drag'             => '',		
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		$this->get_query_vars();			
		$this->query_vars['type'] = $this->document_type;						
		
		if ( empty($this->query_vars['include']) )
		{
			$selected = $this->get_filter_value( 'close' );
			if ( $selected )		
			{	
				$this->query_vars['close'] = sanitize_title($selected);		
			}		
		}
		$query = new USAM_Object_Statuses_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>