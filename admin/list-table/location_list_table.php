<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_location extends USAM_List_Table
{	
    private $type_location;
	private $location_list = 0;
	private $location_id_parent;
	
	function __construct( $args = array() )
	{	
       parent::__construct( $args );
		
		$this->location_list = isset($_REQUEST['location_list'])?$_REQUEST['location_list']:$this->location_list;
		$this->location_id_parent = isset($_REQUEST['id_parent']) && $_REQUEST['id_parent'] != '' ?$_REQUEST['id_parent']:0;	
		
		$this->type_location = usam_get_types_location( 'code' );
    }	
	
	public function return_post()
	{
		return array('location_list', 'id_parent');
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
		$url = add_query_arg( array( 'id_parent' => $item->id, 'location_list' => $this->location_list ), $this->url );			
		$title = "<a href='".$url."'>$item->name</a>";		
		$this->row_actions_table( $title, $this->standart_row_actions( $item->id, 'location' ) );
	}
	
	function column_code( $item ) 
	{	
		if ( !empty($item->code) )
			echo $this->type_location[$item->code]->name;		
	}
		
	function column_id( $item ) 
	{	
		if ( $item->id )
			echo $item->id;		
	}		
		
	public function extra_tablenav0( $which ) 
	{			
		echo '<div class="alignleft actions">';		
		if ( 'top' == $which ) 
		{					
			?>				
			<div class = "usam_manage usam_manage-location_list">
				<input type='hidden' value='0' name='location_list' />
				<input id="location_list" type='checkbox' name='location_list' <?php checked( $this->location_list ); ?> value = '1' />
				<label for="location_list"><?php esc_html_e( 'Показать список', 'usam'); ?></label>
			</div>	
			<?php 
		}
		echo '</div>';
	}
	
	public function single_row( $item )
	{
		echo '<tr id = "item-'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'id'        => array('id', false),			
			'code'      => array('code', false),		
			'sort'      => array('sort', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',				
			'name'             => __('Название', 'usam'),
			'id'               => __('Номер', 'usam'),
			'code'             => __('Тип', 'usam'),	
			'sort'             => __('Сортировка', 'usam'),			
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
		if ( empty($this->query_vars['include']) )
		{		
			if ( !$this->location_list && $this->query_vars['search'] == '' )
			{						
				$this->query_vars['parent'] = $this->location_id_parent;					
			}				
		} 
		$query = new USAM_Locations_Query( $this->query_vars );
		$this->items = $query->get_results();					
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
		if ( !$this->location_list && $this->location_id_parent )
		{					
			$location = usam_get_location( $this->location_id_parent );				
			$up = new stdClass(); 
			$up->name = '...';
			$up->id = $location['parent'];
			$up->sort = '';
			$up->type = '';	
			array_unshift($this->items, $up );	
		}			
	}
}
?>