<?php	
require_once( USAM_FILE_PATH . '/includes/automation/trigger.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_triggers extends USAM_List_Table
{	
	function get_bulk_actions_display() 
	{
		$actions = [
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}		
			
	function column_title( $item )
	{
		$this->row_actions_table( $item->title, $this->standart_row_actions( $item->id, 'trigger' ) );
		?><p class="description"><?php echo $item->description; ?></p><?php
	}	
	
	function column_event( $item )
	{
		echo usam_get_title_trigger_list( $item->event );
	}	
	
	function column_actions( $item )
	{
		$lists = usam_get_list_actions_triggers();
		$actions = usam_get_array_metadata( $item->id, 'trigger', 'actions' );	
		foreach ( $actions as $action ) 
			echo isset($lists[$action['id']])?'<p>'.$lists[$action['id']]['title'].'</p>':'';
	}	
   
	function get_sortable_columns()
	{
		$sortable = [
			'name'            => array('name', false),			
			'type'            => array('type', false),
		];
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = [	
			'cb'           => '<input type="checkbox" />',		
			'title'        => __('Название', 'usam'),
			'active'       => __('Активность', 'usam'),	
			'event'        => __('Событие', 'usam'),			
			'actions'      => __('Действия', 'usam')			
        ];		
        return $columns;
    }	
			
	function prepare_items() 
	{
		$this->get_query_vars();						
		if ( empty($this->query_vars['include']) )
		{		
			
		}
		$query = new USAM_Triggers_Query( $this->query_vars );
		$this->items = $query->get_results();		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page) );
		}		
	}
}
?>