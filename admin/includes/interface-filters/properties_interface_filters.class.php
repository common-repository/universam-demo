<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Properties_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return ['property_groups' => ['title' => __('Группы свойств', 'usam'), 'type' => 'checklists', 'query' => ['type' => $this->type_group]]];
	}
}
?>