<?php
class USAM_Tab_couriers extends USAM_Page_Tab
{		
	public function get_title_tab()
	{
		return __('Курьеры', 'usam');
	}
	
	protected function get_tab_forms9()
	{
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'edit_shipped' ) )		
			return [['form' => 'edit', 'form_name' => 'shipped', 'title' => __('Добавить отгрузку', 'usam')]];	
		return array();
	}		
}