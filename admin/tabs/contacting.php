<?php
class USAM_Tab_contacting extends USAM_Tab
{		
	public function __construct()
	{						
		$this->views = ['table'];			
		$this->views[] = 'report';		
		$this->views[] = 'settings';		
	}
	
	public function get_title_tab()
	{
		return __('Обращения клиентов', 'usam');
	}
	
	protected function get_tab_forms()
	{
		$user_id = get_current_user_id(); 
		if ( user_can( $user_id, 'edit_contacting' ) )		
		{	
			if ( $this->table == 'contacting_status' )		
				return [['form' => 'edit', 'form_name' => 'contacting_status', 'title' => __('Добавить', 'usam')]];				
		}	
		return array();
	}	
	
	public function get_settings_tabs() 
	{ 
		return array( 'contacting_status' => array('title' => __('Статусы обращений','usam'), 'type' => 'table' ) );
	}
}