<?php
class USAM_Tab_licenses extends USAM_Page_Tab
{
	public function get_title_tab()
	{
		return __('Подписки и лицензии', 'usam');
	}	

	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'license', 'title' => __('Активировать новую лицензию', 'usam')]];	
	}		
}