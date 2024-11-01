<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/social_network_table.php' );
class USAM_List_Table_fb_groups extends USAM_List_Table_Social_Network
{	
	protected $type_social = 'fb_group';
	
	function column_title( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, 'fb_group' );
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'fb_group' ) );	
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',	
			'image'      => '',			
			'title'      => __('Название', 'usam'),
			'code'       => __('ID группы', 'usam'),							
        );		
        return $columns;
    }
}
?>