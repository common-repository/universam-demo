<?php
class USAM_Tab_projects extends USAM_Tab
{		
	public function get_title_tab()
	{			
		return __('Проекты', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return array( array('form' => 'edit', 'form_name' => 'project', 'title' => __('Добавить проект', 'usam') ) );
	}
	
	protected function load_tab()
	{ 
		USAM_Admin_Assets::work_manager();
	}	
}