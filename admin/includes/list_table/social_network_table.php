<?php
require_once(USAM_FILE_PATH.'/includes/feedback/social_network_profiles_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_Social_Network extends USAM_List_Table
{
	function get_bulk_actions_display() 
	{	
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}		
	
	function column_image( $item ) 
    {
		?><img src="<?php echo $item->photo; ?>"><?php
	}
		
	function column_birthday( $item )
	{
		if ( !empty($item->birthday) )
			_e('Да','usam');
		else
			_e('Нет','usam');
	}		
	
	function get_sortable_columns()
	{
		$sortable = array(
			'name'   => array('name', false),		
			'code'   => array('code', false),		
			);
		return $sortable;
	}
	
	function prepare_items() 
	{		
		$this->get_query_vars();		
		$this->query_vars['type_social'] = $this->type_social;
		$social_network = new USAM_Social_Network_Profiles_Query( $this->query_vars );	
		$this->items = $social_network->get_results();		
			
		$total_items = $social_network->get_total();
		$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
	}
}
?>