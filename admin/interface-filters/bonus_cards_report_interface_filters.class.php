<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class bonus_cards_report_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'triggers' => ['title' => __('Когда начислять', 'usam'), 'type' => 'checklists'],
		];
	}
}
?>