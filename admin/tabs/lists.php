<?php
class USAM_Tab_lists extends USAM_Page_Tab
{
	public function get_title_tab()
	{			
		return __('Списки', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return array( 
			array('form' => 'edit', 'form_name' => 'list', 'title' => __('Добавить список', 'usam') ), 
	//		array('form' => 'edit', 'form_name' => 'yandex_list', 'title' => __('Добавить Яндекс рассылку', 'usam') )
		);	
	}
}