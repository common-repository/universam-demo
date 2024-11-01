<?php
class USAM_Tab_my_files extends USAM_Tab
{
	protected $views = ['grid', 'table'];	
	
	public function get_title_tab()
	{ 	
		return __('Мой файлы', 'usam');	
	}	
		
	protected function get_tab_forms()
	{
		return [['button' => 'add_files', 'title' => __('Добавить файлы', 'usam')], ['button' => 'add_folder', 'title' => __('Добавить папку', 'usam')]];
	}	
}