<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class Advertising_campaigns_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'sources' => ['title' => __('Источники', 'usam'), 'type' => 'checklists'], 
			'transitions' => ['title' => __('Переходы', 'usam'), 'type' => 'numeric'],
		];
	}
}
?>