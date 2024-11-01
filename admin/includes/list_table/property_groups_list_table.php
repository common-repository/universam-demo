<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_Property_Groups extends USAM_List_Table
{
	protected $property_type = 'all';
	protected $orderby = 'sort';	
	protected $order = 'ASC';
	function __construct( $args = array() )
	{			
		parent::__construct( $args );
		USAM_Admin_Assets::sort_fields( 'property_groups' );	
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
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, $this->property_type.'_property_group' ) );		
	}	

	function column_parent_id( $item )
	{	
		if ( $item->parent_id )
		{
			$group = usam_get_property_group( $item->parent_id );
			echo $group['name'];
		}
	}	
   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'type'       => array('type', false),			
			'sort'       => array('sort', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',				
			'name'       => __('Название', 'usam'),		
			'code'       => __('Код', 'usam'),
			'parent_id'  => __('Родитель', 'usam'),			
			'sort'       => __('Сортировка', 'usam'),	
			'drag'       => '&nbsp;',			
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{	
		$this->get_query_vars();
		$this->query_vars['cache_results'] = true;
		$this->query_vars['type'] = $this->property_type;
		if ( $this->search == '' )
		{			
			
		}				
		$query = new USAM_Property_Groups_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>