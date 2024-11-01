<?php
class USAM_Tab_convocation extends USAM_Page_Tab
{
	public function get_title_tab()
	{			
		return __('Собрания и планерки', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'convocation', 'title' => __('Добавить', 'usam')]];
	}
	
	protected function load_tab()
	{ 		
		USAM_Admin_Assets::work_manager();
	}
}