<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class chat_Interface_Filters extends USAM_Interface_Filters
{	
	protected function get_filters( ) 
	{				
		return [
			'users' => ['title' => __('Менеджер', 'usam'), 'type' => 'checklists', 'query' => ['source' => 'employee']],
			'chat_channel' => ['title' => __('Канал', 'usam'), 'type' => 'checklists']
		];	
	}	
	
	public function get_sort( ) 
	{
		return ['date-desc' => __('Сначала новые', 'usam'), 'date-asc' => __('Сначала старые', 'usam'), 'channel-desc' => __('Канал &#8595;', 'usam'), 'channel-asc' => __('Канал &#8593;', 'usam'), 'manager-asc' => __('Менеджер &#8593;', 'usam'), 'manager-desc' => __('Менеджер &#8595;', 'usam')];	
	}
}
?>