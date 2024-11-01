<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_business_processes extends USAM_List_Table
{
	protected $status;	
	protected $orderby = 'start';
	protected $order = 'ASC';
	protected $date_column = 'start';
	protected $period = '';
	
	function __construct( $args = array() )
	{			
		parent::__construct( $args );
	
		if ( isset($_REQUEST['status']) && is_numeric($_REQUEST['status']) )
			$this->status = $_REQUEST['status'];
		elseif ( isset($_REQUEST['status']) && $_REQUEST['status'] == 'all' )
		{
			$this->status = 'all';
		}
		else
			$this->status = 1;						
    }	
	
	public function get_default_primary_column_name() {
		return 'title';
	}
		
	function column_title( $item )
	{	
		$title = '<a class="row-title" href="'.esc_url( add_query_arg( array('form' => 'edit', 'form_name' => 'business_process', 'id' => $item->id), $this->url ) ).'">'.$item->title.'</a>';
		$this->row_actions_table( $title, $this->standart_row_actions( $item->id, 'business_process' ) );	
	}

	function column_manager( $item ) 
	{	
		if ( $item->user_id )
		{
			echo usam_get_manager_name( $item->user_id );
		}
	}	
	
	function column_user( $item )
	{
		if ( $item->user_id != 0)
		{	
			?> 
			<div class="user">
				<img width="32" height="32" class="avatar avatar-32 photo" src="<?php echo get_avatar_url( $item->user_id, array('size' => 32, 'default'=>'mystery' ) ); ?>" alt="" />&nbsp;
				<span><?php echo usam_get_manager_name( $item->user_id ); ?></span>
			</div>
			<?php 
		}				
	} 	
	
    function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam'),
			'participants' => __('Исполнители', 'usam'),
		);
		return $actions;
	}
 
	function get_sortable_columns() 
	{
		$sortable = array(					
			'manager'      => array('user_id', false),				
			'date_insert'  => array('date_insert', false)			
		);
		return $sortable;
	}
			
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox" />',				
			'title'         => __('Название', 'usam'),	
        );
        return $columns;	
    }	
	
	function prepare_items() 
	{			
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
				
		$this->get_query_vars();
									
		$query = new USAM_Business_Processes_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}
?>