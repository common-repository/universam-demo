<?php
class USAM_Tab_banners extends USAM_Page_Tab
{
	public function get_title_tab()
	{			
		return __('Баннеры', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'banners' )		
			return [['form' => 'edit', 'form_name' => 'banner', 'title' => __('Добавить', 'usam') ]];		
		return array();
	}		
}