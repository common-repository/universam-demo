<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class contact_properties_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'property_groups' => ['title' => __('Группы свойств', 'usam'), 'type' => 'checklists', 'query' => ['type' => 'contact']],
			'registration' => ['title' => __('Для регистрации', 'usam'), 'type' => 'checkbox']
		];
	}	
}
?>