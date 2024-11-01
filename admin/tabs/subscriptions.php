<?php
class USAM_Tab_subscriptions extends USAM_Page_Tab
{		
	public function get_title_tab()
	{			
		return __('Подписки', 'usam');	
	}
	
	protected function get_tab_forms()
	{
		return [['form' => 'edit', 'form_name' => 'subscription', 'title' => __('Добавить', 'usam')]];	
	}
}